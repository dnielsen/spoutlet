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
use Platformd\GiveawayBundle\ViewModel\giveaway_show_current_queue_state;
use Platformd\GiveawayBundle\QueueMessage\KeyRequestQueueMessage;
use Platformd\GiveawayBundle\Entity\KeyRequestState;
use Platformd\SpoutletBundle\Util\CacheUtil;

class DealController extends Controller
{

    public function _dealFlashMessageAction($dealId)
    {
        $currentSiteId = $this->getCurrentSiteCached()->getId();
        $currentUser   = $this->getCurrentUser();
        $deal          = $this->getDealRepo()->findOneByIdAndSiteId((int) $dealId, $currentSiteId);
        $keyValue      = null;
        $state         = null;

        if (!$deal) {
            die('No deal found');
        }

        if ($currentUser) {
            $key       = $this->getDealCodeRepo()->getUserAssignedCodeForDeal($currentUser, $deal);
            $keyValue  = $key ? $key->getValue() : null;

            if (!$keyValue) {
                $stateRepo = $this->getKeyRequestStateRepo();
                $state     = $stateRepo->findForUserIdAndDealId($currentUser->getId(), $deal->getId());
            }
        }

        if ($keyValue) { # the user has a key, so let's display it for them

            $group        = $deal->getGroup();
            $groupManager = $this->get('platformd.model.group_manager');

            $data                               = new deal_show_key_data();

            $data->deal_code_is_url             = (bool) $key->getPool()->getKeysAreUrls();
            $data->promotion_assigned_key       = $keyValue;
            $data->promotion_group_slug         = $group ? $group->getSlug() : null;
            $data->is_member_of_promotion_group = $group ? $groupManager->isMember($currentUser, $group) : false;
            $data->promotion_group_name         = $group ? $group->getName() : null;

            $response = $this->render('GiveawayBundle:Deal:_showKey.html.twig', array(
                'data' => $data
            ));

            $this->varnishCache($response, 86400);

            return $response;
        }

        $statesToNotifyUserOf = array(KeyRequestState::STATE_IN_QUEUE, KeyRequestState::STATE_REJECTED, KeyRequestState::STATE_REQUEST_PROBLEM);

        if ($state && in_array($state->getCurrentState(), $statesToNotifyUserOf) && !$state->getUserHasSeenState()) { # they have joined the queue, been rejected or something else

            $data = new giveaway_show_current_queue_state();

            $data->success              = $state->getCurrentState() == KeyRequestState::STATE_IN_QUEUE ? 'success' : 'error';
            $data->current_state        = $state->getCurrentState();
            $data->current_state_reason = $state->getStateReason();

            $cacheFor = 86400;

            if ($state->getCurrentState() != KeyRequestState::STATE_IN_QUEUE) {

                $state->setUserHasSeenState(true);
                $em = $this->getDoctrine()->getEntityManager();

                $em->persist($state);
                $em->flush();

                $cacheFor = 1;
            }

            $response = $this->render('GiveawayBundle:Giveaway:_showCurrentQueueState.html.twig', array(
                'data' => $data
            ));

            $this->varnishCache($response, $cacheFor);

            return $response;
        }

        # at this stage, there are no notifications for the user

        $response = new Response();
        $this->varnishCache($response, 86400);

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

        $data    = new deal_show_main_actions_data();
        $inQueue = false;

        if ($currentUser) {
            $stateRepo = $this->getKeyRequestStateRepo();
            $state     = $stateRepo->findForUserIdAndDealId($currentUser->getId(), $deal->getId());
            $inQueue   = $state ? $state->getCurrentState() == KeyRequestState::STATE_IN_QUEUE : false;
        }

        $data->deal_claim_code_button     = $deal->getClaimCodeButton() ? $mediaPathResolver->getPath($deal->getClaimCodeButton(), array()) : null;
        $data->deal_group_name            = $group ? $group->getName() : null;
        $data->deal_group_slug            = $group ? $group->getSlug() : null;
        $data->deal_has_expired           = $deal->hasExpired();
        $data->deal_has_keys              = $totalAvailableKeys ? $totalAvailableKeys > 0 : false;
        $data->deal_redemption_steps      = $deal->getCleanedRedemptionInstructionsArray();
        $data->deal_slug                  = $deal->getSlug();
        $data->deal_id                    = $deal->getId();
        $data->deal_user_already_redeemed = (bool) $currentlyAssigned;
        $data->is_member_of_deal_group    = $group && $currentUser ? $groupManager->isMember($currentUser, $group) : false;
        $data->deal_show_claim_button     = !$deal->hasExpired() && $data->deal_has_keys && !$inQueue && !$currentlyAssigned;

        $response = $this->render('GiveawayBundle:Deal:_dealShowActions.html.twig', array(
            'data' => $data
        ));

        $this->varnishCache($response, 300);

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

        $cacheTime = ($data->next_expiry_in && $data->next_expiry_in < 86400) ? $data->next_expiry_in : 86400;

        $this->varnishCache($response, $cacheTime, 30);

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
        $correctPermalink = $this->get('platformd.model.comment_manager')->checkThread($deal);

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

        $expiresIn = $deal->getEndsAtUtc() ? $deal->getEndsAtUtc()->format('U') - strtotime('now') : 86400;
        $cacheTime = $expiresIn < 86400 ? $expiresIn : 86400;

        $this->varnishCache($response, $cacheTime);

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

        $lockKey = 'DEAL_QUEUE_ENTRY_' . $slug . '_' . $this->getCurrentUser()->getId();

        if(!$this->getCache()->getLock($lockKey, CacheUtil::DEFAULT_LOCK_DURATION_SECONDS)) {
            return $this->redirect($this->generateUrl('deal_show', array('slug' => $slug)));
        }

        $site = $this->getCurrentSite();

        $deal        = $this->getDealManager()->findOneBySlug($slug, $site);
        $stateRepo   = $this->getKeyRequestStateRepo();
        $currentUser = $this->getCurrentUser();
        $userId      = $currentUser->getId();
        $state       = $stateRepo->findForUserIdAndDealId($userId, $deal->getId());

        if ($state) {

            $currentState = $state->getCurrentState();

            if ($currentState == KeyRequestState::STATE_IN_QUEUE) {
                return $this->redirect($this->generateUrl('deal_show', array('slug' => $slug)));
            }

            if ($currentState == KeyRequestState::STATE_ASSIGNED) {
                $assignedKey = $this->getDealCodeRepo()->getUserAssignedCodeForDeal($currentUser, $deal);

                if ($assignedKey) {
                    return $this->redirect($this->generateUrl('deal_show', array('slug' => $slug)));
                }
                # we should let them fall through and get another key... at this stage they seem to have a key, but their keypool / key must have been deleted in the database... so let them get another one.
            }
        }

        $message                 = new KeyRequestQueueMessage();
        $message->keyRequestType = KeyRequestQueueMessage::KEY_REQUEST_TYPE_DEAL;
        $message->promotionId    = $deal->getId();
        $message->dateTime       = new \DateTime();
        $message->slug           = $deal->getSlug();
        $message->userId         = $currentUser->getId();
        $message->siteId         = $this->getCurrentSite()->getId();
        $message->ipAddress      = $this->getClientIp($request);

        $result = $this->getQueueUtil()->addToQueue($message);

        if (!$result) {
            die('Could not add you to the queue... please try again shortly.');
        }

        $em = $this->getDoctrine()->getEntityManager();
        $em->getConnection()->beginTransaction();

        try {
            if (!$state) {
                $state = new KeyRequestState();

                $state->setDeal($deal);
                $state->setUser($currentUser);
                $state->setPromotionType(KeyRequestState::PROMOTION_TYPE_DEAL);
            }

            $state->setCurrentState(KeyRequestState::STATE_IN_QUEUE);
            $state->setStateReason(null);
            $state->setUserHasSeenState(false);

            $em->persist($state);
            $em->flush();
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            $em->close();

            return $this->redirect($this->generateUrl('deal_show', array('slug' => $slug)));
        }

        $path = $this->generateUrl('_deal_flash_message', array('dealId' => $deal->getId()));
        $this->getVarnishUtil()->banCachedObject($path, array('userId' => $userId), true);

        $path = $this->generateUrl('_deal_show_actions', array('dealId' => $deal->getId()));
        $this->getVarnishUtil()->banCachedObject($path, array('userId' => $userId), true);

        $this->getCache()->releaseNamedLock($lockKey);

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
        return $this->get('platformd.repository.comment_thread');
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

    protected function getKeyRequestStateRepo()
    {
        return $this->container->get('pd_giveaway.entity.repository.key_request_state');
    }
}
