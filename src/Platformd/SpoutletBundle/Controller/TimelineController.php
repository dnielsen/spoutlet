<?php

namespace Platformd\SpoutletBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

class TimelineController extends Controller
{
    public function indexAction(Request $request)
    {
        $timeline = $this
            ->getDoctrine()
            ->getEntityManager()
            ->getRepository('SpoutletBundle:Timeline')
            ->findOneBy(array('site' => $this->getCurrentSite()));

        return $this->render('SpoutletBundle:Timeline:index.html.twig', array(
            'timeline' => $timeline
        ));
    }
}


