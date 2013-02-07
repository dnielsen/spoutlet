<?php

namespace Platformd\EventBundle\Repository;

use Platformd\SpoutletBundle\Entity\Group;
use Platformd\EventBundle\Repository\EventRepository;
use Platformd\UserBundle\Entity\User;

class GroupEventRepository extends EventRepository
{
    public function findUpcomingEventsForGroupMostRecentFirst(Group $group, $limit=null)
    {
        $qb = $this->getBaseGroupQueryBuilder($group)
            ->andWhere('ge.endsAt > :now')
            ->andWhere('ge.published = 1')
            ->orderBy('ge.startsAt')
            ->setParameter('now', new \DateTime('now'));

        $this->addActiveClauses($qb);

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function findPastEventsForGroupMostRecentFirst(Group $group, $limit=null)
    {
        $qb = $this->getBaseGroupQueryBuilder($group)
            ->andWhere('ge.endsAt < :now')
            ->andWhere('ge.published = 1')
            ->orderBy('ge.endsAt', 'DESC')
            ->setParameter('now', new \DateTime('now'));

        $this->addActiveClauses($qb);

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    private function getBaseGroupQueryBuilder(Group $group, $alias = 'ge')
    {
        $qb = $this->createQueryBuilder($alias)
            ->andWhere($alias.'.group = :group')
            ->setParameter('group', $group);

        return $qb;
    }

    protected function addActiveClauses($qb, $alias='ge')
    {
        return $qb->andWhere($alias.'.deleted = 0')
            ->andWhere($alias.'.approved = 1');
    }

    /**
     * Returns list of events that the user is registered for or owns
     *
     * @param \Platformd\SpoutletBundle\Entity\User $user
     * @param boolean $whereIsOrganizer
     */
    public function getEventListForUser(User $user, $whereIsOrganizer = false)
    {
        $qb = $this->createQueryBuilder('ge')
            ->select('ge', 'count(a.id) attendeeCount')
            ->leftJoin('ge.attendees', 'a')
            ->andWhere('ge.endsAt >= :now')
            ->groupBy('ge.id')
            ->setParameters(array(
                'user' => $user,
                'now' => new \DateTime('now'),
            ));

        $this->addActiveClauses($qb);

        if ($whereIsOrganizer) {
            $qb->andWhere('ge.user = :user');
        } else {
            $qb->andWhere('ge.id IN (SELECT ge2.id FROM EventBundle:GroupEvent ge2 LEFT JOIN ge2.attendees a2 WHERE a2=:user)')
                ->andWhere('ge.user <> :user')
                ->andWhere('ge.published = 1');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns list of past events that the user is registered for
     *
     * @param \Platformd\SpoutletBundle\Entity\User $user
     */
    public function getPastEventListForUser(User $user)
    {
        $qb = $this->createQueryBuilder('ge')
            ->select('ge', 'count(a.id) attendeeCount')
            ->leftJoin('ge.attendees', 'a')
            ->andWhere('ge.id IN (SELECT ge2.id FROM EventBundle:GroupEvent ge2 LEFT JOIN ge2.attendees a2 WHERE a2=:user)')
            ->andWhere('ge.endsAt < :now')
            ->andWhere('ge.published = 1')
            ->groupBy('ge.id')
            ->setParameters(array(
                'user' => $user,
                'now' => new \DateTime('now'),
            ));

        $this->addActiveClauses($qb);

        return $qb->getQuery()->getResult();
    }

    public function getPendingApprovalEventsForGroup(Group $group)
    {
        $qb = $this->createQueryBuilder('ge')
            ->select('ge', 'g')
            ->leftJoin('ge.group', 'g')
            ->where('ge.approved = false')
            ->andWhere('ge.group = :group')
            ->setParameter('group', $group)
            ->orderBy('ge.createdAt', 'DESC')
        ;

        return $qb->getQuery()->getResult();
    }
}
