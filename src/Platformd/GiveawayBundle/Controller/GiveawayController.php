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

        return $this->render('GiveawayBundle:Giveaway:show.html.twig', array(
            'giveaway' => $giveaway,
            'redemptionSteps' => $giveaway->getRedemptionInstructionsArray(),
        ));
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