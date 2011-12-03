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
        $giveaway = $this->findGiveaway($slug);

        $pool = $giveaway->getActivePool();

        if ($keyId) {
            $assignedKey = $this->getKeyRepository()->findOneByIdAndUser($keyId, $this->getUser());
        } else {
            $assignedKey = null;
        }

        return $this->render('GiveawayBundle:Giveaway:show.html.twig', array(
            'giveaway'          => $giveaway,
            'redemptionSteps'   => $giveaway->getRedemptionInstructionsArray(),
            'available_keys'    => $this->getKeyRepository()->getUnassignedForPoolForDisplay($pool),
            'assignedKey'       => $assignedKey,
        ));
    }

    public function keyAction($slug, Request $request)
    {
        // force a valid user
        $this->basicSecurityCheck(array('ROLE_USER'));

        $giveaway = $this->findGiveaway($slug);

        $pool = $giveaway->getActivePool();

        if (!$pool) {
            // repeated below if there is no unassigned keys
            $this->setFlash('error', 'platformd.giveaway.no_keys_left');

            return $this->redirect($this->generateUrl('giveaway_show', array('slug' => $slug)));
        }

        // check the IP limit
        if (!$this->getKeyRepository()->canIpHaveMoreKeys($request->getClientIp(), $pool)) {
            $this->setFlash('error', 'platformd.giveaway.max_ip_limit');

            return $this->redirect($this->generateUrl('giveaway_show', array('slug' => $slug)));
        }

        // does this user already have a key?
        if ($this->getKeyRepository()->doesUserHaveKeyForGiveaway($this->getUser(), $giveaway)) {
            $this->setFlash('error', 'platformd.giveaway.already_assigned');

            return $this->redirect($this->generateUrl('giveaway_show', array('slug' => $slug)));
        }

        $key = $this->getKeyRepository()
            ->getUnassignedKey($pool)
        ;

        if (!$key) {
            $this->setFlash('error', 'platformd.giveaway.no_keys_left');

            return $this->redirect($this->generateUrl('giveaway_show', array('slug' => $slug)));
        }

        // assign this key to this user - record ip address
        $key->assign($this->getUser(), $request->getClientIp());
        $this->getDoctrine()->getEntityManager()->flush();

        return $this->redirect($this->generateUrl('giveaway_show', array(
            'slug' => $slug,
            'keyId' => $key->getId(),
        )));


    }

    /**
     * @param $slug
     * @return \Platformd\GiveawayBundle\Entity\Giveaway
     * @throws \Symfony\Bundle\FrameworkBundle\Controller\NotFoundHttpException
     */
    protected function findGiveaway($slug)
    {
        if (!$giveaway = $this->getRepository()->findOneBySlug($slug, $this->getLocale())) {
            throw $this->createNotFoundException();
        }

        // not technically needed - findOneBySlug checks this... but this reads better, feels safer
        if ($giveaway->isDisabled()) {
            throw $this->createNotFoundException('Giveaway is disabled');
        }

        return $giveaway;
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