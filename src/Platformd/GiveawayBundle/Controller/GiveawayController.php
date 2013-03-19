<?php

namespace Platformd\GiveawayBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Platformd\GiveawayBundle\Entity\MachineCodeEntry;

/**
*
*/
class GiveawayController extends Controller
{

    public function indexAction()
    {
        $active    = array();
        $expired   = array();
        $giveaways = $this->getRepository()->findActives($this->getCurrentSite());
        $featured  = $this->getRepository()->findActiveFeaturedForSite($this->getCurrentSite());
        $comments  = $this->getCommentRepository()->findCommentsForGiveaways($this->getCurrentSite());

        foreach ($giveaways as $giveaway) {
            $keyRepo = $this->getKeyRepository();
            if($keyRepo->getTotalUnassignedKeysForPools($giveaway->getGiveawayPools()) == 0) {
                array_push($expired, $giveaway);
            } else {
                array_push($active, $giveaway);
            }
        }

        return $this->render('GiveawayBundle:Giveaway:index.html.twig', array(
            'giveaways' => $active,
            'featured'  => $featured,
            'expired'   => $expired,
            'comments'  => $comments,
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

        $canTest = $giveaway->getTestOnly() && $this->isGranted(array('ROLE_ADMIN', 'ROLE_SUPER_ADMIN'));

        $availableKeys = ($giveaway->getStatus() == "active" || $canTest) ? $this->getKeyRepository()->getUnassignedForPoolForDisplay($pool) : 0;

        $instruction = $giveaway->getCleanedRedemptionInstructionsArray();

        return $this->render('GiveawayBundle:Giveaway:show.html.twig', array(
            'giveaway'          => $giveaway,
            'redemptionSteps'   => $instruction,
            'available_keys'    => $availableKeys,
            'assignedKey'       => $assignedKey,
        ));
    }

    /**
     * The action that actually assigns a key to a user
     *
     * @param $slug
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function keyAction($slug, Request $request)
    {
        if ($slug == 'dota-2') {
            //return $this->render('GiveawayBundle:Giveaway:dota.html.twig');
        }

        // force a valid user
        $this->basicSecurityCheck(array('ROLE_USER'));
        $user = $this->getUser();

        $giveaway = $this->findGiveaway($slug);
        $giveawayShow = $this->generateUrl('giveaway_show', array('slug' => $slug));

        $canTest = $giveaway->getTestOnly() && $this->isGranted(array('ROLE_ADMIN', 'ROLE_SUPER_ADMIN'));
        if (!$giveaway->getStatus() == "active" && !$canTest) {
            $this->setFlash('error', 'platformd.giveaway.not_eligible');

            return $this->redirectToShow($giveawayShow);
        }

        // make sure this is the type of giveaway that actually allows this
        if (!$giveaway->allowKeyFetch()) {
            throw new AccessDeniedException('This giveaway does not allow you to fetch keys');
        }

        $countryRepo    = $this->getDoctrine()->getEntityManager()->getRepository('SpoutletBundle:Country');
        $country        = $countryRepo->findOneByCode(strtoupper($user->getCountry()));

        if (!$country) {
            $this->setFlash('error', 'deal_redeem_invalid_country');
            return $this->redirect($giveawayShow);
        }

        // check that they pass the new style age-country restriction ruleset
        if ($giveaway->getRuleset() && !$giveaway->getRuleset()->doesUserPassRules($user, $country)) {
            $this->setFlash('error', 'platformd.giveaway.not_eligible');
            return $this->redirect($giveawayShow);
        }

        $pool = $giveaway->getActivePool();

        if (!$pool) {
            // repeated below if there is no unassigned keys
            $this->setFlash('error', 'platformd.giveaway.no_keys_left');

            return $this->redirect($giveawayShow);
        }

        $clientIp = $request->getClientIp(true);

        // check the IP limit
        if (!$this->getKeyRepository()->canIpHaveMoreKeys($clientIp, $pool)) {
            $this->setFlash('error', 'platformd.giveaway.max_ip_limit');

            return $this->redirect($giveawayShow);
        }

        // does this user already have a key?
        if ($this->getKeyRepository()->doesUserHaveKeyForGiveaway($this->getUser(), $giveaway)) {
            $this->setFlash('error', 'platformd.giveaway.already_assigned');

            return $this->redirect($giveawayShow);
        }

        $key = $this->getKeyRepository()
            ->getUnassignedKey($pool)
        ;

        if (!$key) {
            $this->setFlash('error', 'platformd.giveaway.no_keys_left');

            return $this->redirect($giveawayShow);
        }

        // assign this key to this user - record ip address
        $key->assign($this->getUser(), $clientIp, $this->getLocale());
        $this->getDoctrine()->getEntityManager()->flush();

        return $this->redirect($this->generateUrl('giveaway_show', array(
            'slug' => $slug,
            'keyId' => $key->getId(),
        )));
    }

    /**
     * Submits a machine code for a user
     *
     * @param $slug
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function machineCodeAction($slug, Request $request)
    {
        if ($slug == 'dota-2') {
            //return $this->render('GiveawayBundle:Giveaway:dota.html.twig');
        }

        // force a valid user
        $this->basicSecurityCheck(array('ROLE_USER'));
        $user = $this->getUser();

        $giveaway = $this->findGiveaway($slug);
        $giveawayShow = $this->generateUrl('giveaway_show', array('slug' => $slug));

        $canTest = $giveaway->getTestOnly() && $this->isGranted(array('ROLE_ADMIN', 'ROLE_SUPER_ADMIN'));
        if (!$giveaway->getStatus() == "active" && !$canTest) {
            $this->setFlash('error', 'platformd.giveaway.not_eligible');

            return $this->redirectToShow($giveawayShow);
        }

        // make sure this is the type of giveaway that actually allows this
        if (!$giveaway->allowMachineCodeSubmit()) {
            throw new AccessDeniedException('This giveaway does not allow you to submit a machine code');
        }

        if (!$code = $request->request->get('machine_code')) {
            $this->createNotFoundException('No machine code submitted');
        }

        $countryRepo    = $this->getDoctrine()->getEntityManager()->getRepository('SpoutletBundle:Country');
        $country        = $countryRepo->findOneByCode(strtoupper($user->getCountry()));

        if (!$country) {
            $this->setFlash('error', 'deal_redeem_invalid_country');
            return $this->redirect($giveawayShow);
        }

        // check that they pass the new style age-country restriction ruleset
        if ($giveaway->getRuleset() && !$giveaway->getRuleset()->doesUserPassRules($user, $country)) {
            $this->setFlash('error', 'platformd.giveaway.not_eligible');
            return $this->redirect($giveawayShow);
        }

        $clientIp = $request->getClientIp(true);

        $machineCode = new MachineCodeEntry($giveaway, $code);
        $machineCode->attachToUser($this->getUser(), $clientIp);
        $machineCode->setSiteAppliedFrom($this->getCurrentSite());

        $em = $this->getDoctrine()->getEntityManager();
        $em->persist($machineCode);
        $em->flush();

        $this->setFlash('success', $this->trans('platformd.sweepstakes.entered.message'));

        return $this->redirect($this->generateUrl('giveaway_show', array(
            'slug' => $slug,
        )));
    }

    /**
     * @param $slug
     * @return \Platformd\GiveawayBundle\Entity\Giveaway
     * @throws \Symfony\Bundle\FrameworkBundle\Controller\NotFoundHttpException
     */
    protected function findGiveaway($slug)
    {
        if (!$giveaway = $this->getRepository()->findOneBySlug($slug, $this->getCurrentSite())) {
            throw $this->createNotFoundException();
        }

        /*
         * Commented out, because the new functionality calls for this UrL
         * to be "public" so that demo links can be sent out
         *
        if ($giveaway->isDisabled()) {
            throw $this->createNotFoundException('Giveaway is disabled');
        }
        */

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

    /**
     * @return \Platformd\SpoutletBundle\Entity\Repository\CommentRepository
     */
    protected function getCommentRepository()
    {
        return $this
            ->getDoctrine()
            ->getEntityManager()
            ->getRepository('SpoutletBundle:Comment');
    }
}
