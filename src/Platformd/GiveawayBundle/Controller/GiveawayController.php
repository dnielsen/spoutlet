<?php

namespace Platformd\GiveawayBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;

/**
* 
*/
class GiveawayController extends Controller
{

    public function indexAction()
    {
        $giveaways = $this->getRepository()->findActives($this->getLocale());

        return $this->render('GiveawayBundle:Giveaway:index.html.twig', array(
            'giveaways' => $giveaways
        ));
    }

    public function showAction($slug)
    {
        if (!$giveaway = $this->getRepository()->findOneBySlug($slug, $this->getLocale())) {
            
            throw $this->createNotFoundException();
        }

        $pool = $giveaway->getActivePool();
        $keys = $this
            ->getDoctrine()
            ->getEntityManager()
            ->getRepository('GiveawayBundle:GiveawayKey');

        return $this->render('GiveawayBundle:Giveaway:show.html.twig', array(
            'giveaway'          => $giveaway,
            'redemptionSteps'   => $giveaway->getRedemptionInstructionsArray(),
            'available_keys'    => $keys->getUnassignedForPool($pool)
        ));
    }

    public function keyAction($slug)
    {
        if (!$giveaway = $this->getRepository()->findOneBySlug($slug, $this->getLocale())) {
            
            throw $this->createNotFoundException();
        }

        $user = $this->get('security.context')->getToken()->getUser();
        $pool = $giveaway->getActivePool();
        $key = $this
            ->getDoctrine()
            ->getEntityManager()
            ->getRepository('GiveawayBundle:GiveawayKey')
            ->getUnassignedKey($pool)->assign($user);

        $this->getDoctrine()->getEntityManager()->flush();

        return $this->redirect($this->generateUrl('giveaway_show', array('slug' => $slug)));
    }

    /**
     * @return \Platformd\GiveawayBundle\Entity\GiveawayRepository
     */
    protected function getRepository()
    {

        return $this
            ->getDoctrine()
            ->getEntityManager()
            ->getRepository('GiveawayBundle:Giveaway');
    }
}