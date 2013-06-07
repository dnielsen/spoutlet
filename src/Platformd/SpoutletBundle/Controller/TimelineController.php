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
            ->findOneBy(array('site' => $this->getCurrentSite()->getId()));

        $response = $this->render('SpoutletBundle:Timeline:index.html.twig', array(
            'timeline' => $timeline
        ));

        $this->varnishCache($response, 300, 300);

        return $response;
    }
}


