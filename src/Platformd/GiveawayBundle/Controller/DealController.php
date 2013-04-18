<?php

namespace Platformd\GiveawayBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Platformd\GiveawayBundle\ViewModel\deal_show_data;
use Platformd\SpoutletBundle\Entity\ThreadRepository;
use Platformd\SpoutletBundle\Entity\Thread;
use Platformd\GiveawayBundle\ViewModel\deal_index_data;
use Platformd\GiveawayBundle\ViewModel\deal_show_main_actions_data;
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
use Platformd\CEVOBundle\Api\ApiException;
use Platformd\GiveawayBundle\ViewModel\deal_show_key_data;

class DealController extends Controller
{

    public function _dealFlashMessageAction($dealId)
    {
        $currentSiteId = $this->getCurrentSiteCached()->getId();
        $currentUser   = $this->getCurrentUser();
        $deal          = $this->getDealRepo()->findOneByIdAndSiteId((int) $dealId, $currentSiteId);

        if ($currentUser) {
            $key      = $this->getDealCodeRepo()->getUserAssignedCodeForDeal($currentUser, $deal);
            $keyValue = $key ? $key->getValue() : null;
        } else {
            $keyValue = null;
        }

        if (!$deal) {
            die('No giveaway found');
        }

        $group        = $deal->getGroup();
        $groupManager = $this->get('platformd.model.group_manager');

        if ($keyValue) {

            $data                     = new deal_show_key_data();
            $data->deal_code_assigned = $keyValue;
            $data->deal_code_is_url   = (bool) $key->getPool()->getKeysAreUrls();

            if ($group) {
                $data->deal_group_name = $group->getName();
                $data->deal_group_slug = $group->getSlug();
            }

            $response = $this->render('GiveawayBundle:Deal:_showKey.html.twig', array(
                'data' => $data
            ));
        } else {
            $response = new Response();
        }

        $response->setSharedMaxAge(30);

        return $response;
    }


    public function _dealShowActionsAction($dealId)
    {
        $dealId        = (int) $dealId;
        $currentSiteId = $this->getCurrentSiteCached()->getId();
        $deal          = $this->getDealRepo()->findOneByIdWithOpengraphAndMediaForSiteId($dealId, $this->getCurrentSiteId());

        if (!$deal) {
            return $this->generateErrorPage();
        }

        $currentUser       = $this->getCurrentUser();
        $dealCodeRepo      = $this->getDealCodeRepo();
        $dealManager       = $this->getDealManager();
        $mediaPathResolver = $this->getMediaPathResolver();
        $groupManager      = $this->get('platformd.model.group_manager');

        $totalAvailableKeys = $dealCodeRepo->getTotalAvailableForDeal($deal);
        $currentlyAssigned  = $currentUser ? $dealCodeRepo->getUserAssignedCodeForDeal($currentUser, $deal) : null;
        $group              = $deal->getGroup();

        $data = new deal_show_main_actions_data();

        $data->deal_claim_code_button     = $deal->getClaimCodeButton() ? $mediaPathResolver->getPath($deal->getClaimCodeButton(), array()) : null;
        $data->deal_group_name            = $group ? $group->getName() : null;
        $data->deal_group_slug            = $group ? $group->getSlug() : null;
        $data->deal_has_expired           = $deal->hasExpired();
        $data->deal_has_keys              = $totalAvailableKeys ? $totalAvailableKeys > 0 : false;
        $data->deal_redemption_steps      = $deal->getCleanedRedemptionInstructionsArray();
        $data->deal_slug                  = $deal->getSlug();
        $data->deal_user_already_redeemed = (bool) $currentlyAssigned;
        $data->is_member_of_deal_group    = $group ? $groupManager->isMember($currentUser, $group) : false;

        $response = $this->render('GiveawayBundle:Deal:_dealShowActions.html.twig', array(
            'data' => $data
        ));

        $response->setSharedMaxAge(30);

        return $response;
    }

    /**
     * The main deals "list" page
     * @Template
     */
    public function indexAction()
    {
        $data = $this->getDealManager()->getAnonDealIndexData();

        if (!$data) {
            return $this->generateErrorPage();
        }

        $response = $this->render('GiveawayBundle:Deal:index.html.twig', array('data' => $data));

        $response->setSharedMaxAge(30);
        $response->setMaxAge(30);

        return $response;
    }

    /**
     * @param string $slug
     * @Template()
     */
    public function showAction($slug, Request $request)
    {
        $deal = $this->getDealRepo()->findOneBySlugWithOpengraphAndMediaForSiteId($slug, $this->getCurrentSiteId());

        if (!$deal) {
            return $this->generateErrorPage();
        }

        $threadId         = $deal->getThreadId();
        $thread           = $this->getCommentThreadRepo()->find($threadId);
        $correctPermalink = $this->getLinkableManager()->link($deal).'#comments';

        if (!$thread) {
            $thread = new Thread();
            $thread->setId($threadId);
            $thread->setPermalink($correctPermalink);

            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($thread);
            $em->flush();
        }

        $mediaPathResolver = $this->getMediaPathResolver();
        $openGraph         = $deal->getOpenGraphOverride();
        $ogDescription     = $openGraph ? $openGraph->getDescription() ?: $deal->getDescription() : $deal->getDescription();
        $ogDescription     = strlen($ogDescription) > 140 ? substr($ogDescription, 0, 139)."…" : $ogDescription;
        $ogThumbnail       = $openGraph ? $openGraph->getThumbnail() ?: $deal->getThumbnailLarge() : $deal->getThumbnailLarge();
        $ogThumbnail       = $ogThumbnail ? $mediaPathResolver->getPath($ogThumbnail, array()) : null;

        $mediaGalleryMedias = $deal->getMediaGalleryMedias();
        $medias             = array();

        if ($mediaGalleryMedias) {
            foreach($mediaGalleryMedias as $media) {
                $medias[] = $mediaPathResolver->getPath($media, array());
            }
        }

        $data                                 = new deal_show_data();

        $data->deal_banner                    = $deal->getBanner() ? $mediaPathResolver->getPath($deal->getBanner(), array()) : null;
        $data->deal_bottom_color              = $deal->getBottomColor();
        $data->deal_comment_permalink         = $correctPermalink;
        $data->deal_comment_thread_id         = $threadId;
        $data->deal_description               = $deal->getDescription();
        $data->deal_ends_at_utc               = $deal->getEndsAtUtc();
        $data->deal_has_expired               = $deal->hasExpired();
        $data->deal_has_keys                  = $this->getDealCodeRepo()->getTotalAvailableForDeal($deal) > 0;
        $data->deal_id                        = $deal->getId();
        $data->deal_legal_verbiage            = $deal->getLegalVerbiage();
        $data->deal_media_gallery_media       = $medias;
        $data->deal_name                      = $deal->getName();
        $data->deal_slug                      = $deal->getSlug();
        $data->deal_visit_website_button      = $deal->getVisitWebsiteButton() ? $mediaPathResolver->getPath($deal->getVisitWebsiteButton(), array()) : null;
        $data->deal_website_url               = $deal->getWebsiteUrl();
        $data->opengraph_override_description = $ogDescription;
        $data->opengraph_override_thumbnail   = $ogThumbnail;

        $response = $this->render('GiveawayBundle:Deal:show.html.twig', array('data' => $data));

        $response->setSharedMaxAge(30);
        $response->setMaxAge(30);

        return $response;
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
        $locale         = $site->getDefaultLocale();
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

        $country = $this->getCurrentCountry();

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
        if($joinGroup && $this->getCurrentSite()->getSiteFeatures()->getHasGroups()) {
            if($deal->getGroup()) {
                $groupManager = $this->getGroupManager();
                $group = $deal->getGroup();

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

    protected function getDealCodeRepo()
    {
        return $this
            ->getDoctrine()
            ->getEntityManager()
            ->getRepository('GiveawayBundle:DealCode');
    }

    protected function getCommentThreadRepo() {
        return $this->get('platformd.entity.repository.comment_thread');
    }

    protected function getLinkableManager() {
        return $this->get('platformd.link.linkable_manager');
    }

    protected function getMediaPathResolver() {
        return $this->get('platformd.media_path_resolver');
    }

    protected function getDealRepo()
    {
        return $this
            ->getDoctrine()
            ->getEntityManager()
            ->getRepository('GiveawayBundle:Deal');
    }
}
