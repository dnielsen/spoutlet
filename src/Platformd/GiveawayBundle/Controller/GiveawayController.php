<?php

namespace Platformd\GiveawayBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Platformd\GiveawayBundle\Entity\MachineCodeEntry;
use Platformd\GroupBundle\Entity\GroupMembershipAction;
use Platformd\GroupBundle\Event\GroupEvent;
use Platformd\GroupBundle\GroupEvents;
use Platformd\CEVOBundle\Api\ApiException;
use Platformd\GiveawayBundle\ViewModel\giveaway_show_main_actions_data;
use Platformd\GiveawayBundle\ViewModel\giveaway_show_key_data;

/**
*
*/
class GiveawayController extends Controller
{

    public function _giveawayFlashMessageAction($giveawayId)
    {
        $currentSiteId = $this->getCurrentSiteCached()->getId();
        $currentUser   = $this->getCurrentUser();
        $giveaway      = $this->getRepository()->findOneByIdAndSiteId((int) $giveawayId, $currentSiteId);

        if ($currentUser) {
            $key      = $this->getKeyRepository()->getUserAssignedCodeForGiveaway($currentUser, $giveaway);
            $keyValue = $key ? $key->getValue() : null;
        } else {
            $keyValue = null;
        }

        if (!$giveaway) {
            die('No giveaway found');
        }

        $group        = $giveaway->getGroup();
        $groupManager = $this->get('platformd.model.group_manager');

        if ($keyValue) {

            $data = new giveaway_show_key_data();
            $data->giveaway_assigned_key = $keyValue;

            $response = $this->render('GiveawayBundle:Giveaway:_showKey.html.twig', array(
                'data' => $data
            ));
        } else {
            $response = new Response();
        }

        $response->setSharedMaxAge(30);

        return $response;
    }

    public function _giveawayShowActionsAction($giveawayId)
    {
        $giveawayId    = (int) $giveawayId;
        $currentSiteId = $this->getCurrentSiteCached()->getId();
        $giveaway      = $this->getRepository()->findOneByIdAndSiteId($giveawayId, $currentSiteId);

        if (!$giveaway) {
            return $this->generateErrorPage();
        }

        $currentUser     = $this->getCurrentUser();
        $giveawayManager = $this->getGiveawayManager();
        $data            = new giveaway_show_main_actions_data();

        $data->giveaway_available_keys = $giveawayManager->getAvailableKeysForGiveaway($giveaway);

        if ($currentUser) {
            $data->can_user_apply_to_giveaway = !$this->getMachineCodeEntryRepository()->activeOrPendingExistsForUserIdAndGiveawayId($currentUser->getId(), $giveaway->getId());

            $testMode = $giveaway->getTestOnly() && $currentUser->hasRole('ROLE_SUPER_ADMIN');

            if ($giveaway->getStatus() != 'active' && !$testMode) {
                $data->giveaway_available_keys             = 0;
                $data->can_user_apply_to_giveaway = true;
            }

        } elseif ($giveaway->getStatus() != 'active') { #not logged in
            $data->giveaway_available_keys             = 0;
            $data->can_user_apply_to_giveaway = true;
        }

        $group        = $giveaway->getGroup();
        $groupManager = $this->get('platformd.model.group_manager');

        $data->promotion_group_slug         = $group ? $group->getSlug() : null;
        $data->is_member_of_promotion_group = $group ? $groupManager->isMember($currentUser, $group) : false;
        $data->promotion_group_name         = $group ? $group->getName() : null;

        $assignedKey = $this->getKeyRepository()->getUserAssignedCodeForGiveaway($currentUser, $giveaway);

        $data->giveaway_slug                      = $giveaway->getSlug();
        $data->giveaway_show_keys                 = $giveaway->getShowKeys();
        $data->giveaway_allow_key_fetch           = $giveaway->allowKeyFetch();
        $data->giveaway_allow_machine_code_submit = $giveaway->allowMachineCodeSubmit();
        $data->giveaway_redemption_steps          = $giveaway->getCleanedRedemptionInstructionsArray();
        $data->giveaway_show_get_key_button       = $giveaway->allowKeyFetch() && $data->giveaway_available_keys > 0 && !$assignedKey;

        $response = $this->render('GiveawayBundle:Giveaway:_giveawayShowActions.html.twig', array(
            'data' => $data
        ));

        $response->setSharedMaxAge(30);

        return $response;
    }

    public function indexAction()
    {
        $active    = array();
        $expired   = array();
        $giveaways = $this->getRepository()->findActives($this->getCurrentSite());
        $featured  = $this->getRepository()->findActiveFeaturedForSite($this->getCurrentSite());
        $comments  = $this->getCommentRepository()->findCommentsForGiveaways();

        foreach ($giveaways as $giveaway) {
            $keyRepo = $this->getKeyRepository();
            if($keyRepo->getTotalUnassignedKeysForPools($giveaway->getGiveawayPools()) == 0) {
                array_push($expired, $giveaway);
            } else {
                array_push($active, $giveaway);
            }
        }

        $response = $this->render('GiveawayBundle:Giveaway:index.html.twig', array(
            'giveaways' => $active,
            'featured'  => $featured,
            'expired'   => $expired,
            'comments'  => $comments,
        ));

        $response->setSharedMaxAge(30);
        $response->setMaxAge(30);

        return $response;
    }

    public function showAction($slug, $keyId)
    {
        $data = $this->getGiveawayManager()->getAnonGiveawayShowData($slug);

        if (!$data) {
            return $this->generateErrorPage();
        }

        $response = $this->render('GiveawayBundle:Giveaway:show.html.twig', array('data' => $data));

        $response->setSharedMaxAge(30);
        $response->setMaxAge(30);

        return $response;
    }

    private function getGiveawayManager()
    {
        return $this->container->get('pd_giveaway.giveaway_manager');
    }

    /**
     * The action that actually assigns a key to a user
     *
     * @param $slug
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function keyAction($slug, Request $request, $joinGroup=true)
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

        # if user has elected to join the group associated with this deal, we add them to the list of members
        if($joinGroup && $this->getCurrentSite()->getSiteFeatures()->getHasGroups()) {
            if($giveaway->getGroup()) {
                $groupManager = $this->getGroupManager();
                $group = $giveaway->getGroup();

                if ($groupManager->isAllowedTo($user, $group, $this->getCurrentSite(), 'JoinGroup')) {
                    // TODO This should probably be refactored to use the global activity table
                    $joinAction = new GroupMembershipAction();
                    $joinAction->setGroup($group);
                    $joinAction->setUser($user);
                    $joinAction->setAction(GroupMembershipAction::ACTION_JOINED);

                    $group->getMembers()->add($user);
                    $group->getUserMembershipActions()->add($joinAction);

                    // TODO Add a service layer for managing groups and dispatching such events
                    /** @var \Symfony\Component\EventDispatcher\EventDispatcher $dispatcher */
                    $dispatcher = $this->get('event_dispatcher');
                    $event = new GroupEvent($group, $user);
                    $dispatcher->dispatch(GroupEvents::GROUP_JOIN, $event);

                    $groupManager->saveGroup($group);

                    if($group->getIsPublic()) {
                        try {
                            $response = $this->getCEVOApiManager()->GiveUserXp('joingroup', $user->getCevoUserId());
                        } catch (ApiException $e) {

                        }
                    }
                }
            }
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

    protected function getMachineCodeEntryRepository()
    {
        return $this->getDoctrine()->getEntityManager()->getRepository('GiveawayBundle:MachineCodeEntry');
    }

    protected function getKeyRepository()
    {

        return $this
            ->getDoctrine()
            ->getEntityManager()
            ->getRepository('GiveawayBundle:GiveawayKey');
    }

    /**
     * @return \Platformd\GroupBundle\Model\GroupManager
     */
    private function getGroupManager()
    {
        return $this->get('platformd.model.group_manager');
    }

    /**
     * @return \Platformd\CEVOBundle\Api\ApiManager
     */
    private function getCEVOApiManager()
    {
        return $this->get('pd.cevo.api.api_manager');
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
