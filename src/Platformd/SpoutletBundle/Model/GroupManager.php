<?php

namespace Platformd\SpoutletBundle\Model;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Platformd\SpoutletBundle\Entity\Group;
use Platformd\SpoutletBundle\Entity\GroupNews;
use Platformd\SpoutletBundle\Entity\GroupVideo;
use Platformd\SpoutletBundle\Entity\GroupImage;
use Doctrine\ORM\EntityManager;
use Platformd\SpoutletBundle\Entity\GamePageLocale;
use Symfony\Component\HttpFoundation\Session;
use Knp\MediaBundle\Util\MediaUtil;
use Platformd\SpoutletBundle\Locale\LocalesRelationshipHelper;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Platformd\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    public function __construct(EntityManager $em, Session $session, MediaUtil $mediaUtil, SecurityContextInterface $securityContext)
    {
        $this->em = $em;
        $this->session = $session;
        $this->mediaUtil = $mediaUtil;
        $this->securityContext = $securityContext;
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

        $this->em->persist($group);

        $this->handleMediaFields($group);

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

    /**
     * @return \Platformd\SpoutletBundle\Entity\GroupRepository
     */
    private function getRepository()
    {
        return $this->em->getRepository('SpoutletBundle:Group');
    }
}
