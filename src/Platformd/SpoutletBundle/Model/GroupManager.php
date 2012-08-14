<?php

namespace Platformd\SpoutletBundle\Model;

use Platformd\SpoutletBundle\Entity\Group;
use Platformd\SpoutletBundle\Entity\GroupNews;
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

    private $localesHelper;

    private $securityContext;

    public function __construct(EntityManager $em, Session $session, MediaUtil $mediaUtil, LocalesRelationshipHelper $localesHelper, SecurityContextInterface $securityContext)
    {
        $this->em = $em;
        $this->session = $session;
        $this->mediaUtil = $mediaUtil;
        $this->localesHelper = $localesHelper;
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

        $this->localesHelper->processLocalesSave($group);
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

    public function ensureGroupIsVisible($group)
    {
        if (!$group) {
            throw new NotFoundHttpException('The group does not exist');
        }

        if ($group->getDeleted()) {
            throw new NotFoundHttpException('The group does not exist'); // make sure this group hasn't been marked as deleted
        }

        $locale = $this->session->getLocale();

        if (!$group->getAllLocales() && !in_array($locale, $group->getLocales())) { // make sure this group is visible for this site
            throw new NotFoundHttpException('The group does not exist');
        }
    }

    public function isCurrentUserAllowedToEditGroup($group)
    {
        if (!$group) {
            throw new NotFoundHttpException('The group does not exist');
        }

        $user = $this->securityContext->getToken()->getUser();
        $isAdmin = $this->securityContext->isGranted('ROLE_ADMIN');

        return $group->isOwner($user) || $isAdmin;
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
    }

    /**
     * @return \Platformd\SpoutletBundle\Entity\GroupRepository
     */
    private function getRepository()
    {
        return $this->em->getRepository('SpoutletBundle:Group');
    }
}
