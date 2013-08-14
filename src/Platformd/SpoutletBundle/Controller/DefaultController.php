<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\GiveawayBundle\Entity\Giveaway;
use Platformd\GiveawayBundle\Entity\Deal;
use Platformd\SweepstakesBundle\Entity\Sweepstakes;
use Platformd\SpoutletBundle\Entity\Contest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Platformd\SpoutletBundle\Controller\Controller as Controller;
use Symfony\Component\HttpFoundation\Cookie;

class DefaultController extends Controller
{
    public function _mainUserStripAction() {
        $incompleteAccount = $this->getCurrentUser() ? ($this->getCurrentUser()->getFacebookId() && !$this->getCurrentUser()->getPassword()) : false;
        $response = $this->render('SpoutletBundle::_mainUserStrip.html.twig', array(
            'incompleteAccount' => $incompleteAccount,
        ));

        $this->varnishCache($response, 86400);

        return $response;
    }

    public function _checkAccountCompleteAction(Request $request)
    {
        if (!$this->isGranted('ROLE_USER')) {
            $response = new Response();
            $this->varnishCache($response, 86400);

            return $response;
        }

        $user = $this->getCurrentUser();
        $accountComplete = $this->getUserManager()->isUserAccountComplete($user);

        if ($accountComplete) {
            $response = new Response();
            $this->varnishCache($response, 86400);

            return $response;
        }

        $response = $this->render('SpoutletBundle::_checkAccountComplete.html.twig');
        $this->varnishCache($response, 0);
        return $response;
    }

    public function _flashMessageAction(Request $request)
    {
        if (!$this->isGranted('ROLE_USER')) {
            $response = new Response();
            $this->varnishCache($response, 86400);

            return $response;
        }

        $flashes = $this->getFlash();

        if (!$flashes) {
            $response = new Response();
            $this->varnishCache($response, 86400);

            return $response;
        }

        $response = $this->render('SpoutletBundle::_flashMessage.html.twig', array(
            'type' =>  $flashes['type'],
            'message' =>  $flashes['message'],
        ));

        $this->varnishCache($response, 0);
        return $response;
    }

    public function _layoutFooterAction() {
        $response = $this->render('SpoutletBundle::_footer.html.twig');

        $this->varnishCache($response, 86400);

        return $response;
    }

    public function _groupsAction(Request $request)
    {
        $em     = $this->getDoctrine()->getEntityManager();
        $repo   = $em->getRepository('GroupBundle:Group');
        $site   = $this->getCurrentSite();

        $featured = $repo->findAllFeaturedGroupsForSite($site);
        $popular  = $repo->findPopularGroupsForHomepage($site);

        $response = $this->render('SpoutletBundle:Default:_groups.html.twig', array(
            'featured' => $featured,
            'popular'  => $popular,
        ));

        $this->varnishCache($response, 30);

        return $response;
    }

    public function _pollsAction(Request $request)
    {
        $response = $this->render('SpoutletBundle:Default:_polls.html.twig');

        $this->varnishCache($response, 30);

        return $response;
    }

    public function _arpAction(Request $request)
    {
        # keeping this here until we take over arp. for now we redirect to cevos
        #$response = $this->render('SpoutletBundle:Default:_arp.html.twig');
        #
        #$this->varnishCache($response, 30);
        $baseUrl    = 'http://www.alienwarearena.com%s/arp';
        $subDomain  = $this->getCurrentSite()->getSubDomain() == 'latam' ? '/'.$this->getCurrentSite()->getSubDomain() : '/';
        $url        = sprintf($baseUrl, $subDomain);

        $response = new RedirectResponse($url);

        return $response;
    }

    public function _userArpAction(Request $request, $uuid)
    {
        if ($uuid) {
            $response = new Response();
            $response->headers->set('Content-type', 'text/json; charset=utf-8');

            $url = sprintf("http://alienwarearena.com/arp/getuserarp/%s/", $uuid);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $result = curl_exec($ch);

            curl_close($ch);

            $data = json_decode($result, true);

            if (isset($data['arp'])) {
                $response->setContent(json_encode(array('arp' => $data['arp'])));
            } else {
                $response->setContent(json_encode(array('arp' => 0)));
            }

            $this->varnishCache($response, 600);

            return $response;
        }
    }

    public function forumsAction(Request $request)
    {
        $baseUrl    = 'http://www.alienwarearena.com/%s/forums';
        $subDomain  = $this->getCurrentSite()->getSubDomain();
        $url        = sprintf($baseUrl, $subDomain);

        return new RedirectResponse($url);
    }

    public function forceLogoutAction(Request $request, $returnUrl) {

        $request->getSession()->invalidate();
        $this->getSecurity()->setToken(null);

        $baseHost = $this->getParameter('base_host');

        if (false !== strpos($returnUrl, '/esi/USER_SPECIFIC/')) {
            $returnUrl = '/';
        }

        $response = new RedirectResponse($returnUrl);

        $response->headers->clearCookie('aw_session', '/', $baseHost);
        $response->headers->clearCookie('PHPSESSID', '/', $baseHost);

        return $response;
    }

    public function sessionCookieAction()
    {
        return new Response('');
    }

    public function setApiSessionCookieAction($uuid, $expires, Request $request)
    {
        $return   = $request->get('return') ? urldecode($request->get('return')) : $this->generateUrl('default_index');
        $response = new RedirectResponse($return);

        if (!$uuid || !$expires) {
            return $response;
        }

        $cookieName     = 'awa_session_key';
        $cookieValue    = $uuid;
        $cookieExpiry   = \DateTime::createFromFormat('U', $expires);
        $cookiePath     = '/';
        $cookieHost     = '.'.$this->getParameter('base_host');

        $cookie = new Cookie($cookieName, $cookieValue, $cookieExpiry, $cookiePath, $cookieHost, false, false);
        $response->headers->setCookie($cookie);

        return $response;
    }

    public function refreshApiSessionCookieAction($uuid, Request $request)
    {
        $response = new Response('');

        if (!$uuid) {
            $this->varnishCache($response, 86400);
            return $response;
        }

        $info = $this->getApiManager()->getSessionInfo($uuid);

        if (!$info) {
            $this->varnishCache($response, 3600);
            return $response;
        }

        if (isset($info['metaData']) && $info['metaData']['status'] != 200) {
            $this->varnishCache($response, 3600);
            return $response;
        }

        $cookieName     = 'awa_session_key';
        $cookieValue    = $uuid;
        $cookieExpiry   = new \DateTime($info['data']['expires']);
        $cookiePath     = '/';
        $cookieHost     = '.'.$this->getParameter('base_host');

        $cookie = new Cookie($cookieName, $cookieValue, $cookieExpiry, $cookiePath, $cookieHost, false, false);
        $response->headers->setCookie($cookie);

        $this->varnishCache($response, 3600);
    }

    public function siteSpringboardAction(Request $request)
    {
        $fallbackCountry = 'US';
        $returnPath      = $request->headers->get('X-Return-Url');

        if (!$returnPath) {
            $returnPath = $this->generateUrl('default_index');
        }

        $siteFeatures = $this->getCurrentSite()->getSiteFeatures();

        if ($siteFeatures->getHasCountrySiteRedirection()) {
            $site = $this->getSiteFromUserCountry() ?: $this->getCurrentSite();
        } else {
            $site = $this->getCurrentSite();
        }

        $cookieName   = 'awa_bypass_redirection';
        $cookieValue  = 1;
        $cookieExpiry = new \DateTime('+1 week');
        $cookiePath   = '/';
        $cookieHost   = '.'.$this->getParameter('base_host');
        $cookie       = new Cookie($cookieName, $cookieValue, $cookieExpiry, $cookiePath, $cookieHost, false, false);

        $port = $request->getPort() && $request->getPort() != "80" ? $request->getPort() : '';
        $url  = $request->getScheme().'://'.$site->getFullDomain().$port.$returnPath;

        $response = new RedirectResponse($url);
        $response->headers->setCookie($cookie);

        return $response;
    }

    /**
     * The homepage!
     *
     * Mostly just a wrapper for
     *  * {% render "SpoutletBundle:Default:banner" %}
     * and
     *  * {% render 'SpoutletBundle:Default:featuredContent' %}
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        if (!$this->getCurrentSite()->getSiteFeatures()->getHasIndex()) {
            throw $this->createNotFoundException();
        }

        return $this->render('SpoutletBundle:Default:index.html.twig');
    }

    public function healthCheckAction() {
        $site      = $this->getCurrentSite();
        $giveaways = $this->getDoctrine()->getEntityManager()->getRepository('GiveawayBundle:Giveaway')->findAllActiveForSiteWithLimit($site);
        $ipAddress = $this->getClientIp($this->getRequest());

        return new Response('OK');
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function hotStoriesAction()
    {
        $news = $this->getNewsRepo()
            ->findMostRecentForSite($this->getCurrentSite(), 13)
        ;

        $response = $this->render('SpoutletBundle:Default:_hotStories.html.twig', array(
            'news'     => $news,
        ));

        $this->varnishCache($response, 30);

        return $response;
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function featuredContentAction()
    {

        $site = $this->getCurrentSite();

        // sweeps
        $sweepstakes = $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('SpoutletBundle:AbstractEvent')
            ->getCurrentSweepstakes($site)
        ;

        $sweepstakes_list = array();
        foreach($sweepstakes as $sweepstake) {
            $sweepstakes_list[] = $sweepstake;
        }

        // giveaways
        $giveaways = $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('GiveawayBundle:Giveaway')
            ->findAllActiveForSiteWithLimit($site)
        ;

        $giveaways_list = array();
        foreach($giveaways as $giveaway) {
            // filter out proper Event objects
            if ($giveaway instanceof Giveaway) {
                $giveaways_list[] = $giveaway;
            }
        }

        // global events
        $globalEvents = $this->getGlobalEventService()
            ->findUpcomingEventsForSiteLimited($site)
        ;

        $events_list = array();
        $all_list = array();
        foreach($globalEvents as $globalEvent) {
            $all_list[]     = $globalEvent;
            $events_list[]  = $globalEvent;
        }

        // group events
        $groupEvents = $this->getGroupEventService()
            ->findUpcomingEventsForSiteLimited($site)
        ;

        foreach ($groupEvents as $groupEvent) {
            $events_list[] = $groupEvent;
        }

        // deals
        $deals = $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('GiveawayBundle:Deal')
            ->findAllActiveDealsForSiteId($site->getId())
        ;

        $deals_list = array();
        foreach ($deals as $deal) {
            $deals_list[] = $deal;
        }

        // contests
        $contests = $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('SpoutletBundle:Contest')
            ->findAllForSiteByDate($site->getDefaultLocale())
        ;

        $contest_list = array();
        foreach ($contests as $contest) {
            $contest_list[] = $contest;
        }

        $combined_list  = array_merge($all_list, $giveaways_list, $sweepstakes_list, $deals_list);
        $other          = array_merge($sweepstakes_list, $deals_list, $contest_list);

        usort($other, function($a, $b) {
            $aDate = $a instanceof Contest ? $a->getSubmissionStart() : $a->getStartsAt();
            $bDate = $b instanceof Contest ? $b->getSubmissionStart() : $b->getStartsAt();

            if ($aDate == $bDate) {
                return 0;
            }

            return $aDate > $bDate ? -1 : 1;

        });

        $other_list = array();
        foreach ($other as $item) {
            if($item instanceof Sweepstakes) {
                $other_list[] = array('name' => $item->getName(), 'target' => '', 'link' => $this->generateUrl('sweepstakes_show', array('slug' => $item->getSlug())));
            }

            if($item instanceof Deal) {
                $other_list[] = array('name' => $item->getName(), 'target' => '', 'link' => $this->generateUrl('deal_show', array('slug' => $item->getSlug())));
            }

            if($item instanceof Contest) {
                $other_list[] = array('name' => $item->getName(), 'target' => '', 'link' => $this->generateUrl('contest_show', array('slug' => $item->getSlug())));
            }
        }

        usort($combined_list, function($a, $b) {

            $aDate = $a instanceof Giveaway ? $a->getCreated() : $a->getStartsAt();
            $bDate = $b instanceof Giveaway ? $b->getCreated() : $b->getStartsAt();

            if ($aDate == $bDate) {
                return 0;
            }

            return $aDate > $bDate ? -1 : 1;

        });

        $response = $this->render('SpoutletBundle:Default:featuredContent.html.twig', array(
            'all_events'     => $combined_list,
            'giveaways'      => $giveaways_list,
            'competitions'   => $events_list,
            'sweepstakes'    => $sweepstakes_list,
            'other'          => $other_list,
        ));

        $this->varnishCache($response, 30);

        return $response;
    }

    public function microsoftAction()
    {
        $image = sprintf('microsoft.%s.jpg', $this->getLocale());
        return $this->render('SpoutletBundle:Default:microsoft.html.twig', array(
            'image' => $image,
        ));
    }

    public function privacyAction()
    {
        $site = $this->getSiteFromUserCountry();

        return $this->render('SpoutletBundle:Default:privacy.html.twig', array(
            'locale' => $site->getDefaultLocale()
        ));
    }

    public function terms_conditionsAction()
    {
        $site = $this->getSiteFromUserCountry();

        return $this->render('SpoutletBundle:Default:terms_conditions.html.twig', array(
            'locale' => $site->getDefaultLocale()
        ));
    }

    public function aboutAction()
    {
        if (!$this->getCurrentSite()->getSiteFeatures()->gethasAbout()) {
            throw $this->createNotFoundException();
        }

        return $this->render('SpoutletBundle:Default:about.html.twig');
    }

    public function contactAction()
    {
        if (!$this->getCurrentSite()->getSiteFeatures()->gethasContact()) {
            throw $this->createNotFoundException();
        }

        return $this->render('SpoutletBundle:Default:contact.html.twig');
    }

    public function newsAction()
    {
        return $this->render('SpoutletBundle:Default:news.html.twig');
    }

    public function wallpapersAction()
    {
        $wallpapers = $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('SpoutletBundle:Wallpaper')
            ->findTopXMostRecentNewestFirst(12)
        ;

        return $this->render('SpoutletBundle:Default:wallpaper.html.twig', array('wallpapers' => $wallpapers));
    }

    public function bannerAction()
    {
        $banners = $this
            ->getDoctrine()
            ->getEntityManager()
            ->getRepository('SpoutletBundle:HomepageBanner')
            ->findForSite($this->getCurrentSite(), 5);

        return $this->render('SpoutletBundle:Default:banner.html.twig', array('banners' => $banners));
    }

    public function militaryAction()
    {
        $locale = $this->getLocale();

        if($locale != 'en_US') {
            throw $this->createNotFoundException('Page not found');
        }

        return $this->render('SpoutletBundle:Default:military.html.twig');
    }

    public function videoFeedAction(Request $request, $height='252')
    {
        $videos = $this->getYoutubeManager()->findFeaturedVideosForCountry($this->getCurrentSite(), $this->getCurrentCountry(), 6);

        $response = $this->render('SpoutletBundle:Default:_videos.html.twig', array(
            'videos' => $videos,
            'height' => $height,
        ));

        $this->varnishCache($response, 30);

        return $response;
    }

    public function eventsAction()
    {
        $site = $this->getCurrentSite();

        $upcomingGlobalEvents = $this->getGlobalEventService()->findUpcomingEventsForSite($site);
        $upcomingGroupEvents  = $this->getGroupEventService()->findUpcomingEventsForSite($site);
        $upcomingEvents       = array_merge($upcomingGlobalEvents, $upcomingGroupEvents);
        uasort($upcomingEvents, array($this->getGlobalEventService(), 'eventCompare'));

        $events = array_slice($upcomingEvents, 0, 6);

        return $this->render('SpoutletBundle:Default:events.html.twig', array('events' => $events));
    }

    public function groupsMapAction()
    {
        $site = $this->getCurrentSite();

        $groups = $this->get('platformd.model.group_manager')->getAllLocationGroupsForSite($site);

        $groupsArray = array();

        foreach ($groups as $group) {
            $groupsArray[] = array(
                'name' => $group->getName(),
                'location' => $group->getLocation(),
                'url' => $this->getLinkableUrl($group),
            );
        }

        return $this->render('SpoutletBundle:Default:groupsMap.html.twig', array('groups' => $groupsArray));
    }

    private function getYoutubeManager()
    {
        return $this->get('platformd.model.youtube_manager');
    }
}
