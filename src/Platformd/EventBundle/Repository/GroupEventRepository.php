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

    protected function addActiveClauses($qb, $alias='e')
    {
        return $qb->andWhere($alias.'.deleted = 0')
            ->andWhere($alias.'.approved = 1');
    }
}
