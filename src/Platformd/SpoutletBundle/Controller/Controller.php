<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\EventBundle\Entity\GroupEvent;
use Platformd\EventBundle\Entity\GlobalEvent;
use Platformd\EventBundle\Entity\EventSession;
use Platformd\GroupBundle\Entity\Group;
use Platformd\IdeaBundle\Entity\EntrySet;
use Platformd\IdeaBundle\Entity\Idea;
use Platformd\IdeaBundle\Entity\HtmlPage;
use Platformd\IdeaBundle\Entity\Sponsor;
use Platformd\SpoutletBundle\Exception\InsufficientAgeException;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Platformd\SpoutletBundle\Util\HttpUtil;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Our custom base controller
 */
class Controller extends BaseController
{
    protected $cache;

    protected function getCurrentSite()
    {
        return $this->container->get('platformd.util.site_util')->getCurrentSite();
    }

    protected function getCurrentSiteCached()
    {
        return $this->container->get('platformd.util.site_util')->getCurrentSiteCached();
    }

    protected function getVarnishUtil()
    {
        return $this->container->get('platformd.util.varnish_util');
    }

    protected function varnishCache($response, $sharedMaxAge, $maxAge = 0)
    {
        $this->getVarnishUtil()->cacheResponse($response, $sharedMaxAge, $maxAge);
    }

    protected function varnishBan($path, $parameters = array(), $bestEffort = false)
    {
        $this->getVarnishUtil()->banCachedObject($path, $parameters, $bestEffort);
    }

    protected function getCurrentSiteId()
    {
        return $this->getCurrentSiteCached()->getId();
    }

    protected function getCurrentUser()
    {
        $token = $this->container->get('security.token_storage')->getToken();
        $user = $token === null ? null : $token->getUser();

        if ($user === 'anon.') {
            return null;
        }

        return $user;
    }

    protected function getParameter($parameterName)
    {
        return $this->container->getParameter($parameterName);
    }

    protected function getCache()
    {

        if ($this->cache) {
            return $this->cache;
        }

        $this->cache = $this->container->get('platformd.util.cache_util');

        return $this->cache;
    }

    protected function getTemplating()
    {
        return $this->container->get('templating');
    }

    public function generateErrorPage($title = 'platformd.not_found.title', $body = 'platformd.not_found.body')
    {
        return $this->render('SpoutletBundle::error.html.twig',
            array(
                'title' => $title,
                'body' => $body));
    }

    protected function getSiteFromLocale($locale)
    {
        return $this->getDoctrine()->getManager()->getRepository('SpoutletBundle:Site')->findOneByDefaultLocale($locale);
    }

    // See comment in config_dev.yml re. ip_lookup_override parameter when using this function in a dev environment.
    protected function getCurrentCountry()
    {
        $countryCode = $this->getCurrentCountryCode();
        return $this->getDoctrine()->getManager()->getRepository('SpoutletBundle:Country')->findOneByCode($countryCode);
    }

    protected function getCurrentCountryCode()
    {
        return $this->getIpLookupUtil()->getCountryCode($this->getClientIp($this->getRequest()));
    }

    /**
     * @return string
     */
    protected function getLocale()
    {
        return $this->container->get('request')->getLocale();
    }

    /**
     * @return object|\Symfony\Component\Security\Core\Authorization\AuthorizationChecker
     */
    protected function getSecurity()
    {
        return $this->get('security.authorization_checker');
    }

    /**
     * @return \Platformd\GiveawayBundle\Entity\GiveawayRepository
     */
    protected function getGiveawayRepo()
    {
        return $this->getDoctrine()
            ->getManager()
            ->getRepository('GiveawayBundle:Giveaway');
    }

    /**
     * @return \Platformd\SweepstakesBundle\Entity\SweepstakesRepository
     */
    protected function getSweepstakesRepo()
    {
        return $this->getDoctrine()
            ->getManager()
            ->getRepository('SweepstakesBundle:Sweepstakes');
    }

    /**
     * @return \Platformd\NewsBundle\Entity\NewsRepository
     */
    protected function getNewsRepo()
    {
        return $this->getDoctrine()
            ->getManager()
            ->getRepository('NewsBundle:News');
    }

    protected function setFlash($key, $message)
    {
        $this->getFlashUtil()->setFlash($key, $message);
    }

    protected function getFlash()
    {
        return $this->getFlashUtil()->getFlash();
    }

    protected function hasFlash()
    {
        return $this->getFlashUtil()->hasFlash();
    }

    protected function getQueueUtil()
    {
        return $this->container->get('platformd.util.queue_util');
    }

    /**
     * Shortcut for enforcing security
     *
     * @param array $roles
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    protected function basicSecurityCheck($roles)
    {
        if (!$this->container->get('security.authorization_checker')->isGranted($roles)) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    protected function getBreadcrumbs()
    {
        return $this->get('platformd.breadcrumbs_admin_menu');
    }

    /**
     * @return \Platformd\UserBundle\Entity\UserManager
     */
    protected function getUserManager()
    {
        return $this->get('fos_user.user_manager');
    }

    protected function enforceUserSecurity()
    {
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw new AccessDeniedException('Not logged in!');
        }
    }

    /**
     * Checks whether a Linkable object is actually a link to another site
     *
     * @param \Platformd\SpoutletBundle\Link\LinkableInterface $linkableObj
     *
     * @return bool
     */
    protected function isExternalLink(LinkableInterface $linkableObj)
    {
        return HttpUtil::isUrlExternal($this->getLinkableUrl($linkableObj), $this->getRequest()->getHost());
    }

    /**
     * Creates a redirect response to the URL of a Linkable object
     *
     * @param \Platformd\SpoutletBundle\Link\LinkableInterface $linkableObj
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function createLinkableResponse(LinkableInterface $linkableObj)
    {
        $url = $this->getLinkableUrl($linkableObj);

        return new RedirectResponse($url);
    }

    /**
     * Returns the URL for a Linkable object
     *
     * @param \Platformd\SpoutletBundle\Link\LinkableInterface $linkableObj
     *
     * @return string
     */
    protected function getLinkableUrl(LinkableInterface $linkableObj, $absolute = false)
    {
        return $this->container->get('platformd.link.linkable_manager')
            ->link($linkableObj, $absolute);
    }

    /**
     * @param        $key
     * @param array  $params
     * @param string $domain
     *
     * @return mixed
     */
    protected function trans($key, $params = array(), $domain = 'messages', $locale = null)
    {
        return $this->container->get('platformd.model.translator')->trans($key, $params, $domain, $locale);
    }

    protected function transChoice($key, $number, $params = array(), $domain = 'messages', $locale = null)
    {
        return $this->container->get('platformd.model.translator')->transChoice($key, $number, $params, $domain, $locale);
    }

    /**
     * Enforces that the user is a certain age and throws the InsufficientAgeException
     * if he/she is now.
     *
     * @param int $minimumAge
     *
     * @throws \Platformd\SpoutletBundle\Exception\InsufficientAgeException
     */
    protected function enforceAgeProtection($minimumAge = 13)
    {
        # it is really important that when facebook (and other spiders/bots) scrape our pages that we don't block their
        # access (with the Age Verification) otherwise sections like the GamePages won't get indexed by google, and/or
        # the facebook OG scraping won't work... so place any exceptions to age protection here and skip the forced
        # "enter age" page.

        $userAgent = $this->getRequest()->server->get('HTTP_USER_AGENT');

        if (strpos($userAgent, 'facebookexternalhit') !== false) { # facebook OG scrapper user agent = facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)
            return;
        }

        /** @var $ageManager \Platformd\SpoutletBundle\Age\AgeManager */
        $ageManager = $this->container->get('platformd.age.age_manager');

        if ($ageManager->getUsersAge() < $minimumAge) {
            throw new InsufficientAgeException();
        }
    }

    protected function getSiteFromUserCountry()
    {
        $country = $this->getCurrentCountry();

        return $this->getDoctrine()->getManager()->getRepository('SpoutletBundle:Region')->findSiteByCountry($country);
    }

    protected function getSiteFromCountryCode($countryCode)
    {
        $country = $this->getDoctrine()->getManager()->getRepository('SpoutletBundle:Country')->findOneByCode($countryCode);
        return $this->getDoctrine()->getManager()->getRepository('SpoutletBundle:Region')->findSiteByCountry($country);
    }

    /**
     * @return \Platformd\SpoutletBundle\Age\AgeManager
     */
    protected function getAgeManager()
    {
        return $this->container->get('platformd.age.age_manager');
    }

    /**
     * @return \Knp\MediaBundle\Util\MediaUtil
     */
    protected function getMediaUtil()
    {
        return $this->container->get('knp_media.util.media_util');
    }

    protected function getMediaPathResolver()
    {
        return $this->get('platformd.media_path_resolver');
    }

    protected function getIpLookupUtil()
    {
        return $this->container->get('platformd.model.ip_lookup_util');
    }

    protected function getClientIp(Request $request)
    {
        return $this->getIpLookupUtil()->getClientIp($request);
    }

    protected function getGlobalEventService()
    {
        return $this->get('platformd_event.service.global_event');
    }

    protected function getGroupEventService()
    {
        return $this->get('platformd_event.service.group_event');
    }

    protected function getGroupManager()
    {
        return $this->get('platformd.model.group_manager');
    }

    protected function getIdeaService()
    {
        return $this->get('platformd.service.idea');
    }

    protected function getSiteManager()
    {
        return $this->get('platformd.model.site_manager');
    }

    protected function getFlashUtil()
    {
        return $this->container->get('platformd.util.flash_util');
    }

    protected function getAvatarManager()
    {
        return $this->get('platformd.model.avatar_manager');
    }

    protected function getGallaryManager()
    {
        return $this->get('platformd.model.gallary_manager');
    }

    protected function getApiManager()
    {
        return $this->get('platformd.user.api.manager');
    }

    protected function getApiAuth()
    {
        return $this->getParameter('api_authentication');
    }

    protected function uuidGen()
    {
        return str_replace("\n", '', `uuidgen -r`);
    }

    /**
     * @return \Platformd\UserBundle\Security\User\Provider\FacebookProvider
     */
    protected function getFacebookProvider()
    {
        return $this->container->get('platformd.facebook.provider');
    }

    /**
     * @return \Platformd\UserBundle\Security\User\Provider\TwitterProvider
     */
    protected function getTwitterProvider()
    {
        return $this->container->get('platformd.twitter.provider');
    }

    protected function getEmailManager()
    {
        return $this->container->get('platformd.model.email_manager');
    }

    protected function getCommentManager()
    {
        return $this->container->get('platformd.model.comment_manager');
    }

    protected function getErrorMessages(FormInterface $form)
    {
        $errors = array();
        foreach ($form->getErrors() as $key => $error) {
            $template = $error->getMessageTemplate();
            $parameters = $error->getMessageParameters();

            foreach ($parameters as $var => $value) {
                $template = str_replace($var, $value, $template);
            }

            $errors[$key] = $template;
        }

        foreach ($form->all() as $child) {
            if (!$child->isValid()) {
                $errors[$child->getName()] = $this->getErrorMessages($child);
            }
        }

        return $errors;
    }

    public function getParentByIdea($idea)
    {
        $esRegistration = $idea->getParentRegistration();
        $esRegRepo = $this->getDoctrine()->getRepository('IdeaBundle:EntrySetRegistry');

        return $esRegRepo->getContainer($esRegistration);
    }

    public function getParentByEntrySet($entrySet)
    {
        $parentRegistration = $entrySet->getEntrySetRegistration();
        $esRegRepo = $this->getDoctrine()->getRepository('IdeaBundle:EntrySetRegistry');

        return $esRegRepo->getContainer($parentRegistration);
    }

    protected function getBreadCrumbsString($scope, $showCurrentScope = false)
    {
        $breadCrumbs = $this->getHierarchy($scope);

        $breadCrumbsHtml = "";

        foreach ($breadCrumbs as $crumb) {
            if ($crumb && ($showCurrentScope || $crumb != $scope)) {
                $breadCrumbsHtml = $breadCrumbsHtml . "> <a href=\"" . $this->generateUrl($crumb->getLinkableRouteName(), $crumb->getLinkableRouteParameters()) . "\" class=\"blue\">" . $crumb->getName() . "</a> ";
            }
            if ($crumb instanceof EventSession) {
                $event = $crumb->getEvent();
                $breadCrumbsHtml = $breadCrumbsHtml . "> <a href=\"" . $this->generateUrl('event_session_schedule', $event->getLinkableRouteParameters()) . "\" class=\"blue\">Session Schedule</a> ";
            }
        }

        return $breadCrumbsHtml;
    }

    protected function getHierarchy($scope)
    {
        $group = null;
        $event = null;
        $entrySet = null;
        $entry = null;
        $eventSession = null;

        $entrySetParent = null;

        if ($scope instanceof EventSession) {
            $eventSession = $scope;
            $event = $eventSession->getEvent();
            $group = $event->getGroup();
        }
        if ($scope instanceof Idea) {
            $entry = $scope;
            $entrySet = $entry->getEntrySet();
            $entrySetParent = $this->getParentByEntrySet($entrySet);
        } elseif ($scope instanceof EntrySet) {
            $entrySet = $scope;
            $entrySetParent = $this->getParentByEntrySet($entrySet);
        } elseif ($scope instanceof GroupEvent) {
            $event = $scope;
            $group = $event->getGroup();
        } elseif ($scope instanceof Group) {
            $group = $scope;
        }

        if ($entrySetParent instanceof GroupEvent) {
            $event = $entrySetParent;
            $group = $event->getGroup();
        } elseif ($entrySetParent instanceof Group) {
            $group = $entrySetParent;
        }

        return array(
            $group,
            $event,
            $entrySet,
            $entry,
            $eventSession,
        );
    }

    public function getAuthorizedUsers($securedObj, $authorizedUsers = array())
    {
        // TODO: Add co-organizers to the scopes below as authorized users
        if ($securedObj instanceof EventSession) {
            return $this->getAuthorizedUsers($securedObj->getEvent(), $authorizedUsers);

        } elseif ($securedObj instanceof HtmlPage) {
            $authorizedUsers[] = $securedObj->getCreator()->getUsername();
            return $this->getAuthorizedUsers($securedObj->getParent(), $authorizedUsers);

        } elseif ($securedObj instanceof Sponsor) {
            $authorizedUsers[] = $securedObj->getCreator()->getUsername();
            return $this->getAuthorizedUsers($securedObj->getDepartment(), $authorizedUsers);

        } elseif ($securedObj instanceof Idea) {
            $authorizedUsers[] = $securedObj->getCreator()->getUsername();
            return $this->getAuthorizedUsers($securedObj->getEntrySet(), $authorizedUsers);

        } elseif ($securedObj instanceof EntrySet) {
            $authorizedUsers[] = $securedObj->getCreator()->getUsername();
            return $this->getAuthorizedUsers($this->getParentByEntrySet($securedObj), $authorizedUsers);

        } elseif ($securedObj instanceof GroupEvent) {
            $authorizedUsers[] = $securedObj->getUser()->getUsername();
            return $this->getAuthorizedUsers($securedObj->getGroup(), $authorizedUsers);

        } elseif ($securedObj instanceof GlobalEvent) {
            $authorizedUsers[] = $securedObj->getUser()->getUsername();

        } elseif ($securedObj instanceof Group) {
            $authorizedUsers[] = $securedObj->getOwner()->getUsername();
            // Add parent groups here? Up to community level?
        }
        return $authorizedUsers;
    }

    public function isAuthorized($securedObj)
    {
        if (!$user = $this->getCurrentUser()) {
            return false;
        }
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $authorizedUsers = $this->getAuthorizedUsers($securedObj);

        return in_array($user->getUsername(), $authorizedUsers);
    }

    public function validateAuthorization($securedObj)
    {
        if (!$this->isAuthorized($securedObj)) {
            throw new AccessDeniedException();
        }
    }

    public function isAdmin()
    {
        return $this->isGranted('ROLE_ADMIN');
    }

    private function ensureGroupExists($group)
    {
        if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
        }
    }

    public function getGroup($identifier)
    {
        if (ctype_digit($identifier))
            $identifier = intval($identifier);

        if (is_int($identifier))
            return $this->getGroupById($identifier);

        if (is_string($identifier))
            return $this->getGroupBySlug($identifier);

        return null;
    }

    public function getGroupById($id)
    {
        return $this
            ->getDoctrine()
            ->getRepository('GroupBundle:Group')
            ->find($id);
    }

    public function getGroupBySlug($slug)
    {
        $repo = $this->getDoctrine()->getRepository('GroupBundle:Group');
        $group = null;
        $site = $this->getCurrentSite();
        if ($site->getCommunityGroup()) {
            try {
                $group = $repo->findGroupByRelativeSlugAndSite($slug, $site);
            } catch (\Exception $e) {
                //log message
            }
        }

        if (!$group) {
            $group = $repo->findOneBySlug($slug);
        }

        $this->ensureGroupExists($group);

        return $group;
    }

    public function getRelevantSlugForGroup($group)
    {
        if ($this->getCurrentSite()->getCommunityGroup()) {
            if ($slug = $group->getRelativeSlug()) {
                return $slug;
            }
        }
        return $group->getSlug();
    }
}
