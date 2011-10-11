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
    		->findAll();

    	return $this->render('SpoutletBundle:Default:featuredContent.html.twig', array('all_events' => $all_events));
    }

    public function supportAction()
    {
        return $this->render('SpoutletBundle:Default:support.html.twig');
    }
}
