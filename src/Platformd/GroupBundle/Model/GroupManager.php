<?php

namespace Platformd\GroupBundle\Model;

use Platformd\GroupBundle\GroupEvents;
use Platformd\GroupBundle\Entity\Group;
use Platformd\GroupBundle\Entity\GroupApplication;
use Platformd\GroupBundle\Entity\GroupNews;
use Platformd\GroupBundle\Entity\GroupVideo;
use Platformd\GroupBundle\Entity\GroupImage;
use Platformd\GroupBundle\Entity\GroupDiscussion;
use Platformd\GroupBundle\Entity\GroupDiscussionPost;
use Platformd\GroupBundle\Entity\GroupMembershipAction;
use Platformd\GroupBundle\Event\GroupDiscussionEvent;
use Platformd\GroupBundle\Event\GroupDiscussionPostEvent;
use Platformd\GroupBundle\Event\GroupEvent;
use Platformd\EventBundle\Entity\GroupEvent as GroupEventEntity;
use Platformd\SpoutletBundle\Locale\LocalesRelationshipHelper;
use Platformd\SpoutletBundle\Util\SiteUtil;
use Platformd\CEVOBundle\Api\ApiManager;
use Platformd\CEVOBundle\Api\ApiException;
use Platformd\UserBundle\Entity\User;
use Platformd\EventBundle\Service\GroupEventService;
use Platformd\SpoutletBundle\Entity\Site;

use Doctrine\ORM\EntityManager;
use Knp\MediaBundle\Util\MediaUtil;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Manager for Group:
 *
 *  * Handles our special logic for locales
 *  * Saves the media fields
 *  * saves the media gallery
 */
class GroupManager
{
    private $em;

    private $session;

    private $mediaUtil;

    private $siteUtil;

    private $CEVOApiManager;

    private $securityContext;

    private $eventDispatcher;

    private $groupEventService;

    private $isMemberCache;
    private $isApplicantCache;
    private $mediaExposer;

    static private $superAdminIsAllowedTo        = array('ViewGroupContent', 'ViewGroup', 'EditGroup', 'DeleteGroup', 'AddNews', 'EditNews', 'DeleteNews', 'AddImage', 'EditImage', 'DeleteImage', 'AddVideo', 'EditVideo', 'DeleteVideo', 'ManageDiscussions', 'AddDiscussion', 'EditDiscussion', 'DeleteDiscussion', 'ViewDiscussion', 'ManageApplications', 'AddEvent', 'ApproveEvent', 'CancelEvent', 'ViewEvent', 'JoinEvent', 'DeleteEvent', 'JoinGroup', 'ApplyToGroup', 'LeaveGroup', 'ContactGroup');
    static private $ownerIsAllowedTo             = array('ViewGroupContent', 'ViewGroup', 'EditGroup', 'DeleteGroup', 'AddNews', 'EditNews', 'DeleteNews', 'AddImage', 'AddVideo', 'ManageDiscussions', 'AddDiscussion', 'EditDiscussion', 'DeleteDiscussion', 'ViewDiscussion', 'ManageApplications', 'AddEvent', 'ApproveEvent', 'ViewEvent', 'JoinEvent', 'DeleteEvent', 'ContactGroup');
    static private $memberIsAllowedTo            = array('ViewGroupContent', 'ViewGroup', 'AddImage', 'AddVideo', 'AddDiscussion', 'ViewDiscussion', 'AddEvent', 'ViewEvent', 'JoinEvent', 'LeaveGroup');
    static private $nonMemberPublicIsAllowedTo   = array('ViewGroupContent', 'ViewGroup', 'JoinGroup', 'ViewEvent', 'JoinEvent');
    static private $nonMemberPrivateIsAllowedTo  = array('ViewGroup', 'ApplyToGroup');
    static private $applicantIsAllowedTo         = array('ViewGroup');

    public function __construct(
        EntityManager $em,
        Session $session,
        MediaUtil $mediaUtil,
        SiteUtil $siteUtil,
        ApiManager $CEVOApiManager,
        SecurityContextInterface $securityContext,
        EventDispatcherInterface $eventDispatcher,
        GroupEventService $groupEventService,
        $mediaExposer
    )
    {
        $this->em = $em;
        $this->session = $session;
        $this->mediaUtil = $mediaUtil;
        $this->siteUtil = $siteUtil;
        $this->CEVOApiManager = $CEVOApiManager;
        $this->securityContext = $securityContext;
        $this->eventDispatcher = $eventDispatcher;
        $this->groupEventService = $groupEventService;
        $this->isMemberCache = array();
        $this->isApplicantCache = array();
        $this->mediaExposer = $mediaExposer;
    }

    /**
     * Find one by
     *
     * @param $criteria
     * @return object
     */
    public function getGroupBy($criteria)
    {
        return $this->getRepository()->findOneBy($criteria);
    }

    public function getGroupBySlug($slug)
    {
        return $this->getRepository()->findOneBySlug($slug);
    }

    /**
     * Call this to save a Group
     *
     * This must be used instead of persisting it directly
     *
     * @param Group $group
     * @param bool $flush
     */
    public function saveGroup(Group $group, $flush = true)
    {
        if (!$group->getOwner()) {
            $user = $this->securityContext->getToken()->getUser();
            $group->setOwner($user);
        }

        $owner      = $group->getOwner();
        $groups     = $owner->getPdGroups();

        if (!in_array($group, $groups->toArray())) {
            $groups[] = $group;
            $owner->setPdGroups($groups);
        }

        if ($group->getId()) {
            $groupEvents = $this->groupEventService->findAllForGroup($group);

            foreach ($groupEvents as $event) {
                foreach ($event->getSites() as $site) {
                    if (!$group->getSites()->contains($site)) {
                        $event->getSites()->removeElement($site);
                    }
                }

                $this->em->persist($event);
            }
        }

        $this->em->persist($group);

        $this->handleMediaFields($group);

        $this->updateFacebookLikes($group);

        if ($flush) {
            $this->em->flush();
        }
    }

    /**
     * Automatically makes a user join a group
     *
     * @param \Platformd\GroupBundle\Entity\Group $group
     * @param \Platformd\UserBundle\Entity\User $user
     */
    public function autoJoinGroup(Group $group, User $user)
    {
        if ($this->isMember($user, $group) || $group->isOwner($user)) {
            return;
        }

        if (!$this->isAllowedTo($user, $group, $this->getCurrentSite(), 'JoinGroup')) {
            throw new AccessDeniedHttpException('You are not allowed/eligible to do that.');
        }

        // TODO This should probably be refactored to use the global activity table
        $joinAction = new GroupMembershipAction();
        $joinAction->setGroup($group);
        $joinAction->setUser($user);
        $joinAction->setAction(GroupMembershipAction::ACTION_JOINED);

        $group->getMembers()->add($user);
        $group->getUserMembershipActions()->add($joinAction);

        $event = new GroupEvent($group, $user);
        $this->eventDispatcher->dispatch(GroupEvents::GROUP_JOIN, $event);

        $this->saveGroup($group);

        $user->getPdGroups()->add($group);
        $this->em->persist($user);
        $this->em->flush();

        if ($group->getIsPublic()) {
            try {
                $this->CEVOApiManager->GiveUserXp('joingroup', $user->getCevoUserId());
            } catch (ApiException $e) {
                // We do nothing
            }
        }
    }

    /**
     * Automatically makes a user apply to a group
     *
     * @param \Platformd\GroupBundle\Entity\Group $group
     * @param \Platformd\UserBundle\Entity\User $user
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @throws \Exception
     */
    public function autoApplyToGroup(Group $group, User $user, GroupEventEntity $event=null)
    {
        if ($this->isMember($user, $group) || $group->isOwner($user)) {
            return;
        }

        /** @var $applicationRepo \Platformd\GroupBundle\Entity\GroupApplicationRepository */
        $applicationRepo = $this->em->getRepository('GroupBundle:GroupApplication');

        if ($application = $applicationRepo->getOneForGroupAndUser($group, $user)) {
            throw new \Exception('You have already applied to this group!');
        }

        if (!$this->isAllowedTo($user, $group, $this->getCurrentSite(), 'ApplyToGroup')) {
            throw new AccessDeniedHttpException('You are not allowed/eligible to do that.');
        }

        $application = new GroupApplication();

        $application->setGroup($group);
        $application->setApplicant($user);
        $application->setSite($this->getCurrentSite());
        $application->setReason('This is an automated application because user has registered for an event belonging to this group.');

        if ($event) {
            $application->setEvent($event);
        }

        $this->em->persist($application);
        $this->em->flush();
    }

    public function saveGroupNews(GroupNews $groupNews, $flush = true)
    {
        if (!$groupNews->getAuthor()) {
            $user = $this->securityContext->getToken()->getUser();
            $groupNews->setAuthor($user);
        }

        $this->em->persist($groupNews);

        if ($flush) {
            $this->em->flush();
        }
    }

    public function saveGroupImage(GroupImage $groupImage, $flush = true)
    {
        if (!$groupImage->getAuthor()) {
            $user = $this->securityContext->getToken()->getUser();
            $groupImage->setAuthor($user);
        }

        if (!$this->mediaUtil->persistRelatedMedia($groupImage->getImage())) {
            $groupImage->setImage(null);
        }

        $this->em->persist($groupImage);

        if ($flush) {
            $this->em->flush();
        }
    }

    public function saveGroupVideo(GroupVideo $groupVideo, $flush = true)
    {
        if (!$groupVideo->getAuthor()) {
            $user = $this->securityContext->getToken()->getUser();
            $groupVideo->setAuthor($user);
        }

        $this->em->persist($groupVideo);

        if ($flush) {
            $this->em->flush();
        }
    }

    /**
     * Updates a group discussion count
     *
     * @param \Platformd\GroupBundle\Entity\GroupDiscussion $groupDiscussion
     * @param \Symfony\Component\HttpFoundation\Session $session
     * @param \Platformd\UserBundle\Entity\User $user
     */
    public function viewGroupDiscussion(GroupDiscussion $groupDiscussion, Session $session, User $user)
    {
        $groupDiscussionToken = 'groupDiscussion' . $groupDiscussion->getId();

        if (!$session->has($groupDiscussionToken)) {
            $session->set($groupDiscussionToken, true);
            $groupDiscussion->incViewCount(1);
            $this->saveGroupDiscussion($groupDiscussion);

            // We dispatch a GroupDiscussionEvent
            $event = new GroupDiscussionEvent($groupDiscussion, $user);
            $this->eventDispatcher->dispatch(GroupEvents::DISCUSSION_VIEW, $event);
        }
    }

    /**
     * Creates a group discussion
     *
     * @param \Platformd\GroupBundle\Entity\GroupDiscussion $groupDiscussion
     */
    public function createGroupDiscussion(GroupDiscussion $groupDiscussion)
    {
        $this->saveGroupDiscussion($groupDiscussion);

        // We dispatch a GroupDiscussionEvent
        $event = new GroupDiscussionEvent($groupDiscussion);
        $this->eventDispatcher->dispatch(GroupEvents::DISCUSSION_CREATE, $event);

        // arp call
        try {
            $response = $this->CEVOApiManager->GiveUserXp('addcomment', $groupDiscussion->getAuthor()->getCevoUserId());
        } catch (ApiException $e) {

        }
    }

    /**
     * Updates a group discussion
     *
     * @param \Platformd\GroupBundle\Entity\GroupDiscussion $groupDiscussion
     */
    public function updateGroupDiscussion(GroupDiscussion $groupDiscussion)
    {
        $this->saveGroupDiscussion($groupDiscussion);

        // We dispatch a GroupDiscussionEvent
        $event = new GroupDiscussionEvent($groupDiscussion);
        $this->eventDispatcher->dispatch(GroupEvents::DISCUSSION_UPDATE, $event);
    }

    /**
     * Persists a group discussion
     *
     * @param \Platformd\GroupBundle\Entity\GroupDiscussion $groupDiscussion
     * @param bool $flush
     */
    public function saveGroupDiscussion(GroupDiscussion $groupDiscussion, $flush = true)
    {
        if (!$groupDiscussion->getAuthor()) {
            $user = $this->securityContext->getToken()->getUser();
            $groupDiscussion->setAuthor($user);
            $groupDiscussion->setLastUpdatedBy($user);
        }

        $this->em->persist($groupDiscussion);

        if ($flush) {
            $this->em->flush();
        }
    }

    /**
     * "Deletes" a discussion, in fact sets deleted property to true
     *
     * @param \Platformd\GroupBundle\Entity\GroupDiscussion $groupDiscussion
     */
    public function deleteGroupDiscussion(GroupDiscussion $groupDiscussion)
    {
        $groupDiscussion->setDeleted(true);

        $user = $this->securityContext->getToken()->getUser();

        $this->em->persist($groupDiscussion);
        $this->em->flush();

        // We dispatch a GroupDiscussionEvent
        $event = new GroupDiscussionEvent($groupDiscussion, $user);
        $this->eventDispatcher->dispatch(GroupEvents::DISCUSSION_DELETE, $event);
    }

    /**
     * Saves a group discussion post
     *
     * @param \Platformd\GroupBundle\Entity\GroupDiscussionPost $groupDiscussionPost
     * @param bool $flush
     */
    public function saveGroupDiscussionPost(GroupDiscussionPost $groupDiscussionPost, $flush = true)
    {
        if (!$groupDiscussionPost->getAuthor()) {
            $user = $this->securityContext->getToken()->getUser();
            $groupDiscussionPost->setAuthor($user);
        }

        $eventName = ($groupDiscussionPost->getId()) ? GroupEvents::DISCUSSION_POST_UPDATE : GroupEvents::DISCUSSION_POST_CREATE;
        $event = new GroupDiscussionPostEvent($groupDiscussionPost);

        $this->em->persist($groupDiscussionPost);

        if ($flush) {
            $this->em->flush();
        }

        $groupDiscussion = $groupDiscussionPost->getGroupDiscussion();
        $groupDiscussion->setLastUpdatedBy($groupDiscussionPost->getAuthor());
        $groupDiscussion->setLastPostId($groupDiscussionPost->getId());

        $this->em->persist($groupDiscussion);

        $this->em->flush();

        // We dispatch our GroupDiscussionPostEvent
        $this->eventDispatcher->dispatch($eventName, $event);
    }

    public function deleteGroupDiscussionPost(GroupDiscussionPost $groupDiscussionPost, $flush = true)
    {
        $this->em->remove($groupDiscussionPost);

        if ($flush) {
            $this->em->flush();
        }

        $eventName = GroupEvents::DISCUSSION_POST_DELETE;
        $event = new GroupDiscussionPostEvent($groupDiscussionPost);

        // We dispatch our GroupDiscussionPostEvent
        $this->eventDispatcher->dispatch($eventName, $event);
    }

    /**
     * Properly persists or unsets the media fields
     *
     * @param \Platformd\GroupBundle\Entity\Group $group
     */
    private function handleMediaFields(Group $group)
    {
        $mUtil = $this->mediaUtil;

        if (!$mUtil->persistRelatedMedia($group->getBackgroundImage())) {
            $group->setBackgroundImage(null);
        }

        if (!$mUtil->persistRelatedMedia($group->getGroupAvatar())) {
            $group->setGroupAvatar(null);
        }

        if (!$mUtil->persistRelatedMedia($group->getThumbNail())) {
            $group->setThumbNail(null);
        }
    }

    public function updateFacebookLikes(Group $group)
    {
        /*
        format for getting open graph data:
        http://graph.facebook.com/?ids=http://[site].alienwarearena.com/groups/[$group->getId()]/show/
        */

        $total = 0;

        $url = 'http://graph.facebook.com/?ids=';

        $sites = $group->getSites();

        foreach($sites as $site)
        {
            $url .= sprintf('http://%s.alienwarearena.com/groups/%s/show/,', $site->getSubDomain(), $group->getId());
        }

        $url = substr($url, 0, -1);

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:'));

        $results = json_decode(curl_exec($curl), true);

        if ($results) {
            foreach($results as $result)
            {
                if(isset($result))
                {
                    if(array_key_exists('likes', $result))
                    {
                        $total += $result['likes'];
                    }
                }
            }
        }

        $group->setFacebookLikes($total);

        return $total;
    }

    public function findGroupsForFacebookLikesLastUpdatedAt($minutes)
    {
        return $this->getRepository()->findGroupsForFacebookLikesLastUpdatedAt($minutes);
    }

    public function getAllGroupsForUser(User $user)
    {
        return $this->getRepository()->getAllGroupsForUser($user);
    }

    public function getAllGroupsForSite(Site $site)
    {
        return $this->getRepository()->findAllGroupsRelevantForSite($site);
    }
    public function getAllGroupsForSiteSorted(Site $site){
        return $this->getRepository()->findAllGroupsRelevantForSiteSorted($site);
    }

    public function getAllLocationGroupsForSite(Site $site)
    {
        return $this->getRepository()->findAllLocationGroupsRelevantForSite($site);
    }

    private function getCurrentSite()
    {
        return $this->siteUtil->getCurrentSite();
    }

    public function getMembershipCountByGroup($group)
    {
        return $this->getRepository()->getMembershipCountByGroup($group);
    }

    /**
     * @return \Platformd\GroupBundle\Entity\GroupRepository
     */
    private function getRepository()
    {
        return $this->em->getRepository('GroupBundle:Group');
    }

    private function getApplicationRepository()
    {
        return $this->em->getRepository('GroupBundle:GroupApplication');
    }

    public function isAllowedTo($user, $group, $site, $action) {

        return in_array($action, $this->getPermissions($user, $group, $site));
    }

    public function isMember($user, Group $group)
    {
        if (!$user instanceof User) {
            return false;
        }

        $cacheKey = $user->getId().'-'.$group->getId();

        if (array_key_exists($cacheKey, $this->isMemberCache)) {
            return $this->isMemberCache[$cacheKey];
        }

        $repo   = $this->getRepository();
        $result = $repo->isUserMemberOfGroup($user, $group);

        $this->isMemberCache[$cacheKey] = $result;
        return $this->isMemberCache[$cacheKey];
    }

    public function isApplicant($user, Group $group)
    {
        if(!$user instanceof User) {
            return false;
        }

        $cacheKey = $user->getId().'-'.$group->getId();

        if (array_key_exists($cacheKey, $this->isApplicantCache)) {
            return $this->isApplicantCache[$cacheKey];
        }

        $repo   = $this->getApplicationRepository();
        $result =  $repo->isUserApplicantToGroup($user, $group);

        $this->isApplicantCache[$cacheKey] = $result;
        return $this->isApplicantCache[$cacheKey];
    }

    public function getMembersLeftCountByGroup($group, $fromDate=null, $thruDate=null)
    {
        return $this->em->getRepository('GroupBundle:GroupMembershipAction')->getMembersLeftCountByGroup($group, $fromDate, $thruDate);
    }

    public function getMembersJoinedCountByGroup($group, $fromDate=null, $thruDate=null)
    {
        return $this->em->getRepository('GroupBundle:GroupMembershipAction')->getMembersJoinedCountByGroup($group, $fromDate, $thruDate);
    }

    public function getPermissions($user, $group, $site)
    {
        $membershipRequiredActions = array(
            'LeaveGroup',
        );

        $nonMembershipRequiredActions = array(
            'JoinGroup',
            'ApplyToGroup',
        );

        if (!$group->isVisibleOnSite($site)) {
            return array();
        }

        $permissions = array();

        if ($user && $user instanceof User && $user->hasRole('ROLE_USER')) {

            $isSuperAdmin   = $user->hasRole('ROLE_SUPER_ADMIN');
            $isOwner        = $group->isOwner($user);
            $isMember       = $this->isMember($user, $group);
            $isApplicant    = $this->isApplicant($user, $group);

            if ($group->getDeleted()) {
                return $isSuperAdmin ? array('EditGroup') : array();
            }

            if ($isSuperAdmin) {
                $permissions = self::$superAdminIsAllowedTo;
            } elseif ($isOwner) {
                $permissions = self::$ownerIsAllowedTo;
            } elseif ($isMember) {
                $permissions = self::$memberIsAllowedTo;
            } elseif ($isApplicant) {
                $permissions = self::$applicantIsAllowedTo;
            }

            if (count($permissions) > 0) {

                $permissionsMap = array_flip($permissions);

                if (!$this->isMember($user, $group)) {
                    foreach ($membershipRequiredActions as $action) {
                        if(in_array($action, $permissions)) {
                            unset($permissions[$permissionsMap[$action]]);
                        }
                    }
                }

                if ($this->isMember($user, $group)) {
                    foreach ($nonMembershipRequiredActions as $action) {
                        if(in_array($action, $permissions)) {
                            unset($permissions[$permissionsMap[$action]]);
                        }
                    }
                }

                if ($group->getIsPublic()) {
                    if(in_array('ApplyToGroup', $permissions)) {
                        unset($permissions[$permissionsMap['ApplyToGroup']]);
                    }
                } else {
                    if(in_array('JoinGroup', $permissions)) {
                        unset($permissions[$permissionsMap['JoinGroup']]);
                    }
                }

                return $permissions;
            }
        }

        if ($group->getIsPublic()) {
            $permissions = self::$nonMemberPublicIsAllowedTo;
        } else {
            $permissions = self::$nonMemberPrivateIsAllowedTo;
        }

        return $permissions;
    }

    public function find($id)
    {
        return $this->em->getRepository('GroupBundle:Group')->find($id);
    }

    public function findVideoByYoutubeId($group, $youtubeId)
    {
        return $this->em->getRepository('GroupBundle:GroupVideo')->findOneBy(array(
            'group'             => $group->getId(),
            'youTubeVideoId'    => $youtubeId,
        ));
    }

    public function findVideoByYoutubeIdAndUser($group, $youtubeId, $user)
    {
        return $this->em->getRepository('GroupBundle:GroupVideo')->findOneBy(array(
            'group'             => $group->getId(),
            'youTubeVideoId'    => $youtubeId,
            'author'            => $user->getId(),
        ));
    }

    public function getAllDepartmentsForCurrentSite()
    {
        return $this->em->getRepository('GroupBundle:Group')->findAllDepartmentsForSite($this->getCurrentSite());
    }

    public function getGroupIndexData($group)
    {
        if (!$group || !$group instanceof Group) {
            return false;
        }

        $owner    = $group->getOwner();
        $location = $group->getLocation();

        return array(
            'id' => $group->getId(),
            'slug' => $group->getSlug(),
            'urlRouteName' => $group->getLinkableRouteName(),
            'urlRouteParams' => $this->getRouteParamsForGroup($group),
            'thumbnail' => $group->getThumbnail() ? $this->mediaExposer->getPath($group->getThumbnail(), array()) : null,
            'name' => $group->getName(),
            'owner' => array(
                'adminLevel' => $owner->getAdminLevel() == 'ROLE_SUPER_ADMIN',
                'username' => $owner->getUsername(),
            ),
            'city' => $location ? $location->getCity() : '',
            'stateProvince' => $location ? $location->getStateProvince() : '',
            'address1' => $location ? $location->getAddress1() : '',
            'address2' => $location ? $location->getAddress2() : '',
            'metroArea' => $location ? $location->getMetroArea() : '',
            'latitude' => $location ? $location->getLatitude() : '',
            'longitude' => $location ? $location->getLongitude() : '',
        );
    }

    public function getRouteParamsForGroup($group) {
        return array(
            'slug' => $this->getRelevantSlugForGroup($group),
        );
    }

    public function getRelevantSlugForGroup($group) {
        if($this->siteUtil->getCurrentSite()->getCommunityGroup()) {
            if ($slug = $group->getRelativeSlug()){
                return $slug;
            }
        }
        return $group->getSlug();
    }
}
