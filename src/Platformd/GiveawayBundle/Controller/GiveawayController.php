<?php

namespace Platformd\GiveawayBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
* 
*/
class GiveawayController extends Controller
{

    public function indexAction()
    {
        $giveaways = $this->getRepository()->findActives();

        return $this->render('GiveawayBundle:Giveaway:index.html.twig', array(
            'giveaways' => $giveaways
        ));
    }

    public function showAction($slug)
    {
        if (!$giveaway = $this->getRepository()->findOneBy(array('slug' => $slug))) {
            
            throw $this->createNotFoundException();
        }

        return $this->render('GiveawayBundle:Giveaway:show.html.twig', array(
            'giveaway' => $giveaway
        ));
    }

    protected function getRepository()
    {

        return $this
            ->getDoctrine()
            ->getEntityManager()
            ->getRepository('GiveawayBundle:Giveaway');
    }
}