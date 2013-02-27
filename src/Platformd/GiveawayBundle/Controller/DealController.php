<?php

namespace Platformd\GiveawayBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Platformd\SpoutletBundle\Controller\Controller;
use Symfony\Component\Locale\Locale;
use Platformd\GiveawayBundle\Entity\Deal;
use Platformd\GiveawayBundle\Entity\DealCode;
use Symfony\Component\Validator\Constraints\NotBlank;
use Platformd\GroupBundle\Entity\GroupMembershipAction;
use Platformd\GroupBundle\Event\GroupEvent;
use Platformd\GroupBundle\GroupEvents;

class DealController extends Controller
{
    /**
     * The main deals "list" page
     * @Template
     */
    public function indexAction()
    {
        $site = $this->getCurrentSite();

        $featuredDeals = $this->getDealManager()->findFeaturedDeals($site);
        $mainDeal = empty($featuredDeals) ? null : $featuredDeals[0];
        $allDeals = $this->getDealManager()->findActiveDeals($site);
        $expiredDeals = $this->getDealManager()->findExpiredDeals($site);
        $commentsArr = $this->getCommentManager()
            ->findMostRecentCommentsByThreadPrefixWithObjects(Deal::COMMENT_PREFIX, 5)
        ;

        return array(
            'mainDeal'          => $mainDeal,
            'featuredDeals'     => $featuredDeals,
            'allDeals'          => $allDeals,
            'expiredDeals'      => $expiredDeals,
            'commentsArr'       => $commentsArr,
        );
    }

    /**
     * @param string $slug
     * @Template()
     */
    public function showAction($slug, Request $request)
    {

        $em                     = $this->getDoctrine()->getEntityManager();
        $site                   = $this->getCurrentSite();
        $deal                   = $em->getRepository('GiveawayBundle:Deal')->findOneBySlug($slug, $site);
        $dealCodeRepo           = $em->getRepository('GiveawayBundle:DealCode');
        $countries              = Locale::getDisplayCountries('en');

        $user                   = $this->getUser();
        $userAlreadyRedeemed    = false;
        $dealCode               = '';
        $dealCodeIsUrl          = false;

        if (!$deal) {
            throw $this->createNotFoundException('No deal found in this site for slug '.$slug);
        }

        $dealPools  = $deal->getDealPools();

        $loggedIn   = $this->get('security.context')->isGranted('ROLE_USER');

        $hasKeys = $dealCodeRepo->getTotalAvailableForDeal($deal);

        if ($loggedIn) {

            $currentlyAssigned = $dealCodeRepo->getUserAssignedCodeForDeal($user, $deal);

            if ($currentlyAssigned) {
                $userAlreadyRedeemed = true;
                $dealCode = $currentlyAssigned->getValue();
                $dealCodeIsUrl = $currentlyAssigned->getPool()->getKeysAreUrls();
            }
        }

        $instructions = $deal->getCleanedRedemptionInstructionsArray();

        return array(
            'deal' => $deal,
            'userAlreadyRedeemed' => $userAlreadyRedeemed,
            'dealCode' => $dealCode,
            'dealCodeIsUrl' => $dealCodeIsUrl,
            'redemptionSteps' => $instructions,
            'hasKeys' => $hasKeys > 0,
        );
    }

    /**
     * @param string $slug
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @Template()
     */
    public function redeemAction($slug, Request $request, $joinGroup=true)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $em             = $this->getDoctrine()->getEntityManager();
        $site           = $this->getCurrentSite();
        $dealCodeRepo   = $em->getRepository('GiveawayBundle:DealCode');
        $dealPoolRepo   = $em->getRepository('GiveawayBundle:DealPool');
        $deal           = $this->getDealManager()->findOneBySlug($slug, $site);
        $clientIp       = $request->getClientIp(true);
        $user           = $this->getUser();
        $locale         = $this->getLocale();
        $countryRepo    = $em->getRepository('SpoutletBundle:Country');
        $dealShow       = $this->generateUrl('deal_show', array('slug' => $slug));

        $canTest = $deal->getTestOnly() && $this->isGranted(array('ROLE_ADMIN', 'ROLE_SUPER_ADMIN'));
        if ($deal->getStatus() != "published" && !$canTest) {
            $this->setFlash('error', 'deal_not_eligible');

            return $this->redirect($dealShow);
        }

        if ($dealCodeRepo->doesUserHaveCodeForDeal($user, $deal)) {
            $this->setFlash('error', 'deal_redeem_user_already_redeemed');
            return $this->redirect($dealShow);
        }

        $country = $countryRepo->findOneByCode(strtoupper($user->getCountry()));

        if (!$country) {
            $this->setFlash('error', 'deal_redeem_invalid_country');
            return $this->redirect($dealShow);
        }

        // check that they pass the new style age-country restriction ruleset
        if ($deal->getRuleset() && !$deal->getRuleset()->doesUserPassRules($user, $country)) {
            $this->setFlash('error', 'deal_not_eligible');
            return $this->redirect($dealShow);
        }

        $pools = $dealPoolRepo->getAllPoolsForDealGivenCountry($deal, $country);

        if (!$pools || count($pools) < 1) {
            $this->setFlash('error', 'deal_redeem_no_keys_for_your_country');
            return $this->redirect($dealShow);
        }

        $code = null;
        $lastFail = null;

        foreach ($pools as $pool) {

            if (!$dealCodeRepo->canIpHaveMoreKeys($clientIp, $pool)) {
                $lastFail = 'deal_redeem_max_ip_hit';
                continue;
            }

            $code = $dealCodeRepo->getUnassignedKey($pool);

            if (!$code) {
                $lastFail = 'deal_redeem_no_keys_left';
                continue;
            }

            $lastFail = null;
            break;
        }

        if ($lastFail) {
            $this->setFlash('error', $lastFail);
            return $this->redirect($dealShow);
        }

        $code->assign($user, $clientIp, $locale);
        $code->setCountry($country); # in addition to assigning the deal code, we need to set the country (this is one of the differences between a Code and a DealCode)

        # if user has elected to join the group associated with this deal, we add them to the list of members
        if($deal->getGroup()) {
            $group = $deal->getGroup();

            if ($group->isAllowedTo($user, $this->getCurrentSite(), 'JoinGroup')) {
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

                $this->getGroupManager()->saveGroup($group);

                if($group->getIsPublic()) {
                    try {
                        $response = $this->getCEVOApiManager()->GiveUserXp('joingroup', $user->getCevoUserId());
                    } catch (ApiException $e) {

                    }
                }
            }
        }

        $em->flush();

        return $this->redirect($this->generateUrl('deal_show', array('slug' => $slug)));
    }

     /**
     * @return \Platformd\SpoutletBundle\Model\DealManager
     */
    private function getDealManager()
    {
        return $this->get('platformd.model.deal_manager');
    }

    /**
     * @return \Platformd\CommentBundle\Model\CommentManager
     */
    protected function getCommentManager()
    {
        return $this->container->get('fos_comment.manager.comment');
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
}
