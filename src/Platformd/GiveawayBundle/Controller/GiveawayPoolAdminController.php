<?php

namespace Platformd\GiveawayBundle\Controller;

use Platformd\GiveawayBundle\Entity\Giveaway;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;

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
}