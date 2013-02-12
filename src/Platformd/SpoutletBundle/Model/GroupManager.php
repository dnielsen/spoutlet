<?php

namespace Platformd\SpoutletBundle\Model;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Platformd\SpoutletBundle\Entity\Group;
use Platformd\SpoutletBundle\Entity\GroupNews;
use Platformd\SpoutletBundle\Entity\GroupVideo;
use Platformd\SpoutletBundle\Entity\GroupImage;
use Platformd\SpoutletBundle\Entity\GroupDiscussion;
use Platformd\SpoutletBundle\Entity\GroupDiscussionPost;
use Platformd\SpoutletBundle\Event\GroupDiscussionEvent;
use Platformd\SpoutletBundle\Event\GroupDiscussionPostEvent;
use Platformd\SpoutletBundle\GroupEvents;
use Doctrine\ORM\EntityManager;
use Platformd\GameBundle\Entity\GamePageLocale;
use Symfony\Component\HttpFoundation\Session;
use Knp\MediaBundle\Util\MediaUtil;
use Platformd\SpoutletBundle\Locale\LocalesRelationshipHelper;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Platformd\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

    private $securityContext;

    private $eventDispatcher;

    public function __construct(EntityManager $em, Session $session, MediaUtil $mediaUtil, SecurityContextInterface $securityContext, EventDispatcherInterface $eventDispatcher)
    {
        $this->em = $em;
        $this->session = $session;
        $this->mediaUtil = $mediaUtil;
        $this->securityContext = $securityContext;
        $this->eventDispatcher = $eventDispatcher;
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
        $members    = $group->getMembers();

        if (!in_array($owner, $members->toArray())) {
            $members[] = $owner;
            $group->setMembers($members);
        }

        $groupDescription = $group->getDescription();
        $groupDescription = strip_tags($groupDescription, '<p><br><a><strong><em><ol><ul><li>');
        $group->setDescription($groupDescription);

        $this->em->persist($group);

        $this->handleMediaFields($group);

        $this->updateFacebookLikes($group);

        if ($flush) {
            $this->em->flush();
        }
    }

    public function saveGroupNews(GroupNews $groupNews, $flush = true)
    {
        if (!$groupNews->getAuthor()) {
            $user = $this->securityContext->getToken()->getUser();
            $groupNews->setAuthor($user);
        }

        $article = $groupNews->getArticle();
        $article = strip_tags($article, '<p><br><a><strong><em><ol><ul><li>');
        $groupNews->setArticle($article);

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
     * @param \Platformd\SpoutletBundle\Entity\GroupDiscussion $groupDiscussion
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
     * @param \Platformd\SpoutletBundle\Entity\GroupDiscussion $groupDiscussion
     */
    public function createGroupDiscussion(GroupDiscussion $groupDiscussion)
    {
        $this->saveGroupDiscussion($groupDiscussion);

        // We dispatch a GroupDiscussionEvent
        $event = new GroupDiscussionEvent($groupDiscussion);
        $this->eventDispatcher->dispatch(GroupEvents::DISCUSSION_CREATE, $event);
    }

    /**
     * Updates a group discussion
     *
     * @param \Platformd\SpoutletBundle\Entity\GroupDiscussion $groupDiscussion
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
     * @param \Platformd\SpoutletBundle\Entity\GroupDiscussion $groupDiscussion
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
     * @param \Platformd\SpoutletBundle\Entity\GroupDiscussion $groupDiscussion
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
     * @param \Platformd\SpoutletBundle\Entity\GroupDiscussionPost $groupDiscussionPost
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
     * @param \Platformd\SpoutletBundle\Entity\Group $group
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

        $group->setFacebookLikes($total);

        return $total;
    }

    public function findGroupsForFacebookLikesLastUpdatedAt($minutes)
    {
        return $this->getRepository()->findGroupsForFacebookLikesLastUpdatedAt($minutes);
    }

    /**
     * @return \Platformd\SpoutletBundle\Entity\GroupRepository
     */
    private function getRepository()
    {
        return $this->em->getRepository('SpoutletBundle:Group');
    }
}
