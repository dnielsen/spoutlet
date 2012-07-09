<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Event;

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
    public function featuredContentAction()
    {
        $abstractEvents = $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('SpoutletBundle:AbstractEvent')
            ->getCurrentEventsOrderedByCreated($this->getLocale())
        ;

        $news = $this->getNewsRepo()
            ->findMostRecentForLocale($this->getLocale(), 10)
        ;

        // see #64 - we're no longer showing events here
        // we could move this into the query above, but this changes so often, I'm hesitant
        $finalEvents = array();
        foreach ($abstractEvents as $event) {
            // filter out proper Event objects
            if (!$event instanceof Event) {
                $finalEvents[] = $event;
            }
        }

    	return $this->render('SpoutletBundle:Default:featuredContent.html.twig', array(
            'news'     => $news,
            'abstractEvents' => $finalEvents,
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
        return $this->render('SpoutletBundle:Default:military.html.twig');
    }
}
