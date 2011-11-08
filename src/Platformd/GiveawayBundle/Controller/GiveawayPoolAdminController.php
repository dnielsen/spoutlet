<?php

namespace Platformd\GiveawayBundle\Controller;

use Platformd\GiveawayBundle\Entity\Giveaway;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Platformd\GiveawayBundle\Form\Type\GiveawayPoolType;

/**
* 
*/
class GiveawayPoolAdminController extends Controller
{
    
    /**
     * Index action for Giveway pools management
     */
    public function indexAction()
    {
        $pools = $this
            ->getDoctrine()
            ->getEntityManager()
            ->getRepository('GiveawayBundle:GiveawayPool')
            ->findAll();

        return $this->render('GiveawayBundle:GiveawayPoolAdmin:index.html.twig', array(
            'pools' => $pools
        ));
    }

    public function newAction()
    {
        $giveaways = $this
            ->getDoctrine()
            ->getEntityManager()
            ->getRepository('GiveawayBundle:Giveaway')
            ->findAll();

        $form = $this->createForm(new GiveawayPoolType($giveaways));

        return $this->render('GiveawayBundle:GiveawayPoolAdmin:new.html.twig', array(
            'form' => $form->createView()
        ));
    }
}