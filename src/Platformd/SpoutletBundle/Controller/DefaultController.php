<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Event;
use Platformd\GiveawayBundle\Entity\Giveaway;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
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

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function hotStoriesAction()
    {
        $news = $this->getNewsRepo()
            ->findMostRecentForLocale($this->getLocale(), 11)
        ;

        return $this->render('SpoutletBundle:Default:hotStories.html.twig', array(
            'news'     => $news,
        ));
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function featuredContentAction()
    {

        $sweepstakes = $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('SpoutletBundle:AbstractEvent')
            ->getCurrentSweepstakes($this->getLocale(), 10)
        ;

        $sweepstakes_list = array();
        foreach($sweepstakes as $sweepstake) {
            $sweepstakes_list[] = $sweepstake;
        }

        $giveaways = $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('GiveawayBundle:Giveaway')
            ->findAllForLocaleWithLimit($this->getLocale(), 10)
        ;

        $giveaways_list = array();
        foreach($giveaways as $giveaway) {
            // filter out proper Event objects
            if (!$giveaway instanceof Event) {
                $giveaways_list[] = $giveaway;
            }
        }

        $competitions = $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('SpoutletBundle:AbstractEvent')
            ->getCurrentEventsOnly($this->getLocale(), 10)
        ;

        $competitions_list = array();
        foreach($competitions as $competition) {
            $competitions_list[] = $competition;
        }

        $combined_list = array_merge($competitions_list, $giveaways_list, $sweepstakes_list);


    	return $this->render('SpoutletBundle:Default:featuredContent.html.twig', array(
            'all_events'       => $combined_list,
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
            ->findForLocale($this->getLocale());

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
        /*http://chinastaging.alienwarearena.com/video/ajax/apjxml
        http://china.alienwarearena.com/video/ajax/apjxml
        http://japanstaging.alienwarearena.com/video/ajax/apjxml
        http://japan.alienwarearena.com/video/ajax/apjxml*/
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,'http://chinastaging.alienwarearena.com/video/ajax/apjxml');
        curl_setopt($ch, CURLOPT_FAILONERROR,1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        curl_close($ch);

        $xml = simplexml_load_string(trim($response));

        /*var_dump($xml->latest);
        die;*/

        $host = $request->getHost();

        return $this->render('SpoutletBundle:Default:videoFeed.html.twig', array(
            'videos' => $xml,
            'host' => $host,
        ));
    }
}
