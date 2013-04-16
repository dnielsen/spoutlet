<?php

namespace Platformd\SpoutletBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Platformd\SpoutletBundle\Util\HttpUtil;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Platformd\SpoutletBundle\Exception\InsufficientAgeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Our custom base controller
 */
class Controller extends BaseController
{

    protected $cache;

    protected function getCurrentSite() {
        return $this->container->get('platformd.util.site_util')->getCurrentSite();
    }

    protected function getCurrentSiteCached() {
        return $this->container->get('platformd.util.site_util')->getCurrentSiteCached();
    }

    protected function getCurrentSiteId() {
        return $this->getCurrentSiteCached()->getId();
    }

    protected function getCurrentUser() {
        $token = $this->container->get('security.context')->getToken();
        $user  = $token === null ? null : $token->getUser();

        if ($user === 'anon.') {
            return null;
        }

        return $user;
    }

    protected function getCache() {

        if ($this->cache) {
            return $this->cache;
        }

        $this->cache = $this->container->get('platformd.util.cache_util');

        return $this->cache;
    }

    protected function getTemplating() {
        return $this->container->get('templating');
    }

    public function getOrGenResponse($params) {

        $params['withMetaData'] = true;

        $data = $this->getCache()->getOrGen($params);

        # do no replace this isset with array_key_exists as we want to fail EVEN if cachedContent exists but is null
        if ($data === null || !is_array($data) || !isset($data['cachedContent'])) {
            return $this->render('SpoutletBundle::error.html.twig',
                array(
                    'title' => 'platformd.not_found.title',
                    'body'  => 'platformd.not_found.body'));
        }

        return $this->generateCachedResponse($data);
    }

    public function genResponse($html) {
        $data = array('generatedDateTime' => new \DateTime(),
            'cachedContentMd5' => md5($html),
            'cachedContent' => $html);

        return $this->generateCachedResponse($data);
    }

    public function generateCachedResponse($data) {

        $request  = $this->getRequest();
        $response = new Response();

        $response->setEtag($data['cachedContentMd5']);
        $response->setLastModified($data['generatedDateTime']);
        #$response->setPrivate(); # this should NEVER be changed to public... EVER for any reason... non-public stuff uses this function
        $response->setSharedMaxAge(30);

        if ($response->isNotModified($request)) {
            return $response;
        }

        $response->setContent($data['cachedContent']);

        return $response;
    }

    public function generateErrorPage($title = 'platformd.not_found.title', $body = 'platformd.not_found.body') {
        return $this->render('SpoutletBundle::error.html.twig',
            array(
                'title' => $title,
                'body'  => $body));
    }

    /**
     * @return string
     */
    protected function getLocale()
    {
        return $this->container->get('session')->getLocale();
    }

    /**
     * @return \Platformd\GiveawayBundle\Entity\GiveawayRepository
     */
    protected function getGiveawayRepo()
    {
        return $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('GiveawayBundle:Giveaway');
    }

    /**
     * @return \Platformd\SweepstakesBundle\Entity\SweepstakesRepository
     */
    protected function getSweepstakesRepo()
    {
        return $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('SweepstakesBundle:Sweepstakes');
    }

    /**
     * @return \Platformd\NewsBundle\Entity\NewsRepository
     */
    protected function getNewsRepo()
    {
        return $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('NewsBundle:News');
    }

    protected function setFlash($key, $message)
    {
        return $this->container->get('session')->setFlash($key, $message);
    }

    protected function getQueueUtil()
    {
        return $this->container->get('platformd.util.queue_util');
    }

    # this function getUser is only here because it exists in many files... we should no longer use this one and should instead use getCurrentUser()
    protected function getUser()
    {
        return $this->getCurrentUser();
    }

    /**
     * Shortcut for enforcing security
     *
     * @param array $roles
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    protected function basicSecurityCheck($roles)
    {
        if (!$this->container->get('security.context')->isGranted($roles)) {
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

    /**
     * @param $roles
     * @return bool
     */
    protected function isGranted($roles)
    {
        return $this->container->get('security.context')->isGranted($roles);
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
     * @return string
     */
    protected function getLinkableUrl(LinkableInterface $linkableObj)
    {
        return $this->container->get('platformd.link.linkable_manager')
            ->link($linkableObj);
    }

    /**
     * @param $key
     * @param array $params
     * @param string $domain
     * @return mixed
     */
    protected function trans($key, $params = array(), $domain = 'messages', $locale = null)
    {
        if ($locale === null) {
            $locale = $this->getLocale();
        }

        return $this->container->get('translator')->trans($key, $params, $domain, $locale);
    }

    /**
     * Enforces that the user is a certain age and throws the InsufficientAgeException
     * if he/she is now.
     *
     * @param int $minimumAge
     * @throws \Platformd\SpoutletBundle\Exception\InsufficientAgeException
     */
    protected function enforceAgeProtection($minimumAge = 13)
    {
        # it is really important that when facebook (and other spiders/bots) scrape our pages that we don't block their
        # access (with the Age Verification) otherwise sections like the GamePages won't get indexed by google, and/or
        # the facebook OG scraping won't work... so place any exceptions to age protection here and skip the forced
        # "enter age" page.

        $userAgent = $this->getRequest()->server->get('HTTP_USER_AGENT');

        if(strpos($userAgent, 'facebookexternalhit') !== false) { # facebook OG scrapper user agent = facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)
            return;
        }

        /** @var $ageManager \Platformd\SpoutletBundle\Age\AgeManager */
        $ageManager = $this->container->get('platformd.age.age_manager');

        if ($ageManager->getUsersAge() < $minimumAge) {
            throw new InsufficientAgeException();
        }
    }

    /**
     * @return \Platformd\SpoutletBundle\Age\AgeManager
     */
    protected  function getAgeManager()
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
}
