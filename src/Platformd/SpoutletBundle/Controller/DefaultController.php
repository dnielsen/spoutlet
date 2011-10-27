<?php

namespace Platformd\SpoutletBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class DefaultController extends Controller
{
    
    public function indexAction()
    {
        return $this->render('SpoutletBundle:Default:index.html.twig');
    }

    /**
     * @param bool $onlyNews Whether to show *just* the news tab
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function featuredContentAction($onlyNews = false)
    {
    	$all_events = $this->getDoctrine()
    		->getEntityManager()
    		->getRepository('SpoutletBundle:Event')
    		->findPublished($this->get('session')->getLocale());

    	return $this->render('SpoutletBundle:Default:featuredContent.html.twig', array(
            'all_events' => $all_events,
            'onlyNews'   => $onlyNews,
        ));
    }

    public function supportAction()
    {
        return $this->render('SpoutletBundle:Default:support.html.twig');
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

    private function getLocale()
    {
        return $this->getRequest()->getSession()->getLocale();
    }
}
