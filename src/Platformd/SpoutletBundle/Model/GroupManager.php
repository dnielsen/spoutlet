<?php

namespace Platformd\SpoutletBundle\Model;

use Platformd\SpoutletBundle\Entity\Group;
use Doctrine\ORM\EntityManager;
use Platformd\SpoutletBundle\Entity\GamePageLocale;
use Symfony\Component\HttpFoundation\Session;
use Knp\MediaBundle\Util\MediaUtil;
use Platformd\SpoutletBundle\Locale\LocalesRelationshipHelper;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Platformd\SpoutletBundle\Entity\UserInterface;
use Platformd\UserBundle\Entity\User;

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

        $this->em->persist($group);

        $this->localesHelper->processLocalesSave($group);
        $this->handleMediaFields($group);

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
    }

    /**
     * @return \Platformd\SpoutletBundle\Entity\GroupRepository
     */
    private function getRepository()
    {
        return $this->em->getRepository('SpoutletBundle:Group');
    }
}
