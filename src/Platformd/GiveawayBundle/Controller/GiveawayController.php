<?php

namespace Platformd\GiveawayBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

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

    /**
     * @param $slug
     * @param integer $keyId Optional key id that was just assigned
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response
     * @throws \Symfony\Bundle\FrameworkBundle\Controller\NotFoundHttpException
     */
    public function showAction($slug, $keyId)
    {
        if (!$giveaway = $this->getRepository()->findOneBySlug($slug, $this->getLocale())) {
            
            throw $this->createNotFoundException();
        }

        $pool = $giveaway->getActivePool();

        if ($keyId) {
            $assignedKey = $this->getKeyRepository()->findOneByIdAndUser($keyId, $this->getUser());
        } else {
            $assignedKey = null;
        }

        return $this->render('GiveawayBundle:Giveaway:show.html.twig', array(
            'giveaway'          => $giveaway,
            'redemptionSteps'   => $giveaway->getRedemptionInstructionsArray(),
            'available_keys'    => $this->getKeyRepository()->getUnassignedForPool($pool),
            'assignedKey'       => $assignedKey,
        ));
    }

    public function keyAction($slug, Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        if (!$giveaway = $this->getRepository()->findOneBySlug($slug, $this->getLocale())) {
            
            throw $this->createNotFoundException();
        }

        $user = $this->get('security.context')->getToken()->getUser();
        $pool = $giveaway->getActivePool();

        $key = $this->getKeyRepository()
            ->getUnassignedKey($pool)
        ;

        if (!$key) {
            $this->setFlash('error', 'platformd.giveaway.no_keys_left');

            return $this->redirect($this->generateUrl('giveaway_show', array('slug' => $slug)));
        }

        // assign this key to this user - record ip address
        $key->assign($user, $request->getClientIp());
        $this->getDoctrine()->getEntityManager()->flush();

        return $this->redirect($this->generateUrl('giveaway_show', array(
            'slug' => $slug,
            'keyId' => $key->getId(),
        )));


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

    /**
     * @return \Platformd\GiveawayBundle\Entity\Repository\GiveawayKeyRepository
     */
    protected function getKeyRepository()
    {

        return $this
            ->getDoctrine()
            ->getEntityManager()
            ->getRepository('GiveawayBundle:GiveawayKey');
    }
}