<?php

namespace Platformd\GiveawayBundle\Controller;

use Platformd\GiveawayBundle\Entity\GiveawayPool;
use Platformd\GiveawayBundle\Form\Type\GiveawayPoolType;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;


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
        $manager = $this
            ->getDoctrine()
            ->getEntityManager();      
        $pool = new GiveawayPool();
        $request = $this->getRequest();

        $form = $this->createForm(new GiveawayPoolType(), $pool);

        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {
                if ($loadedKeys = $pool->loadKeysFromFile()) {
                    foreach ($loadedKeys as $key) {
                        $manager->persist($key);
                    }
                }

                $manager->persist($pool);
                $manager->flush();
            }
        }

        return $this->render('GiveawayBundle:GiveawayPoolAdmin:new.html.twig', array(
            'form' => $form->createView()
        ));
    }
}