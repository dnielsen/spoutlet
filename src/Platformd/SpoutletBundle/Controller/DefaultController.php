<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\GiveawayBundle\Entity\Giveaway;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DefaultController extends Controller
{
    public function _mainUserStripAction() {
        $response = $this->render('SpoutletBundle::_mainUserStrip.html.twig');

        $this->varnishCache($response, 120);

        return $response;
    }

    public function forceLogoutAction(Request $request, $returnUrl) {

        $request->getSession()->invalidate();
        $this->getSecurity()->setToken(null);

        $baseHost = $this->getParameter('base_host');

        $response = new RedirectResponse($returnUrl);

        $response->headers->clearCookie('aw_session', '/', $baseHost);
        $response->headers->clearCookie('PHPSESSID', '/', $baseHost);

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
        if (!$this->getCurrentSite()->getSiteFeatures()->gethasIndex()) {
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

        return $this->render('SpoutletBundle:Default:hotStories.html.twig', array(
            'news'     => $news,
        ));
    }

    # this function is just here to allow the use of path / router -> generate functions through the site... but ultimately this action isn't called, instead the site protection listener redirects the call to CEVOs server
    public function forumsAction()
    {
        throw $this->createNotFoundException();
    }

    # this function is just here to allow the use of path / router -> generate functions through the site... but ultimately this action isn't called, instead the site protection listener redirects the call to CEVOs server
    public function arpAction()
    {
        throw $this->createNotFoundException();
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function featuredContentAction()
    {

        $site = $this->getCurrentSite();

        $sweepstakes = $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('SpoutletBundle:AbstractEvent')
            ->getCurrentSweepstakes($this->getCurrentSite())
        ;

        $sweepstakes_list = array();
        foreach($sweepstakes as $sweepstake) {
            $sweepstakes_list[] = $sweepstake;
        }

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

        $competitions = $this->getGlobalEventService()->findUpcomingEventsForSiteLimited($site);

        $competitions_list = array();
        foreach($competitions as $competition) {
            $competitions_list[] = $competition;
        }

        $combined_list = array_merge($competitions_list, $giveaways_list, $sweepstakes_list);

        usort($combined_list, function($a, $b) {

            $aDate = $a instanceof Giveaway ? $a->getCreated() : $a->getStartsAt();
            $bDate = $b instanceof Giveaway ? $b->getCreated() : $b->getStartsAt();

            if ($aDate == $bDate) {
                return 0;
            }

            return $aDate > $bDate ? -1 : 1;

        });

        return $this->render('SpoutletBundle:Default:featuredContent.html.twig', array(
            'all_events'     => $combined_list,
            'giveaways'      => $giveaways_list,
            'competitions'   => $competitions_list,
            'sweepstakes'    => $sweepstakes_list,
        ));
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
        return $this->render('SpoutletBundle:Default:privacy.html.twig');
    }

    public function terms_conditionsAction()
    {
        return $this->render('SpoutletBundle:Default:terms_conditions.html.twig');
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

    public function videoFeedAction(Request $request)
    {
        $videos = $this->getYoutubeManager()->findFeaturedVideosForCountry($this->getCurrentSite(), $this->getCurrentCountry(), 6);

        return $this->render('SpoutletBundle:Default:videoFeed.html.twig', array(
            'videos' => $videos,
        ));
    }

    private function getVideoFeedUrl($locale)
    {
        /*http://chinastaging.alienwarearena.com/video/ajax/apjxml
        http://china.alienwarearena.com/video/ajax/apjxml
        http://japanstaging.alienwarearena.com/video/ajax/apjxml
        http://japan.alienwarearena.com/video/ajax/apjxml*/
        switch($locale)
        {
            case 'zh':
                return 'http://china.alienwarearena.com/video/ajax/apjxml';
            case 'ja':
                return 'http://japan.alienwarearena.com/video/ajax/apjxml';
            default:
                return 'http://video.alienwarearena.com/ajax/moviexml';
        }
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
