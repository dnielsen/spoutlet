<?php

namespace Platformd\SpoutletBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class DefaultController extends Controller
{
    
    public function indexAction()
    {
        return $this->render('SpoutletBundle:Default:index.html.twig');
    }

    public function featuredContentAction()
    {
    	$all_events = $this->getDoctrine()
    		->getEntityManager()
    		->getRepository('SpoutletBundle:Event')
    		->findPublished();

    	return $this->render('SpoutletBundle:Default:featuredContent.html.twig', array('all_events' => $all_events));
    }

    public function supportAction()
    {
        return $this->render('SpoutletBundle:Default:support.html.twig');
    }

    public function microsoftAction()
    {
        return $this->render('SpoutletBundle:Default:microsoft.html.twig');
    }

    public function privacyAction()
    {
        return $this->render('SpoutletBundle:Default:privacy.html.twig');
    }

    public function terms_conditionsAction()
    {
        return $this->render('SpoutletBundle:Default:terms_conditions.html.twig');
    }

}
