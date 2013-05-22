<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\GiveawayBundle\Entity\Giveaway;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function _mainUserStripAction() {
        $response = $this->render('SpoutletBundle::_mainUserStrip.html.twig');

        $response->setSharedMaxAge(120);

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
        return $this->render('SpoutletBundle:Default:index.html.twig');
    }

    public function healthCheckAction() {
        $site      = $this->getCurrentSite();
        $giveaways = $this->getDoctrine()->getEntityManager()->getRepository('GiveawayBundle:Giveaway')->findAllActiveForSiteWithLimit($site);
        $ipAddress = $this->getRequest()->getClientIp(true);

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

        $competitions = $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('SpoutletBundle:AbstractEvent')
            ->getCurrentEventsOnly($site)
        ;

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
        return $this->render('SpoutletBundle:Default:about.html.twig');
    }

    public function contactAction()
    {
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
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$this->getVideoFeedUrl($this->getLocale()));
        curl_setopt($ch, CURLOPT_FAILONERROR,1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        curl_close($ch);

        $xml = simplexml_load_string(trim($response));

        $videos = array();
        foreach ($xml->latest->movie as $video) {
            $videos[] = $video;
        }

        $host = $request->getHost();

        return $this->render('SpoutletBundle:Default:videoFeed.html.twig', array(
            'videos' => $videos,
            'host' => $host,
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
}
