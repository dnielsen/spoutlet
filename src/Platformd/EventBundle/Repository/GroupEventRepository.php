<?php

namespace Platformd\EventBundle\Repository;

use Platformd\SpoutletBundle\Entity\Group;
use Platformd\EventBundle\Repository\EventRepository;

class GroupEventRepository extends EventRepository
{
    public function findUpcomingEventsForGroupMostRecentFirst(Group $group, $limit=null)
    {
        $qb = $this->getBaseGroupQueryBuilder($group)
            ->andWhere('ge.endsAt > :now')
            ->orderBy('ge.startsAt')
            ->setParameter('now', new \DateTime('now'));

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function findPastEventsForGroupMostRecentFirst(Group $group, $limit=null)
    {
        $qb = $this->getBaseGroupQueryBuilder($group)
            ->andWhere('ge.endsAt < :now')
            ->orderBy('ge.endsAt', 'DESC')
            ->setParameter('now', new \DateTime('now'));

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function getEventCountForGroup(Group $group)
    {
        $result = $this->getBaseGroupQueryBuilder($group)
            ->select('count(ge.id)')
            ->groupBy('g.id')
            ->getQuery()
            ->getSingleScalarResult();

        return $result;
    }

    private function getBaseGroupQueryBuilder(Group $group, $alias = 'ge')
    {
        $qb = $this->createQueryBuilder($alias)
            ->leftJoin($alias.'.groups', 'g')
            ->andWhere('g = :group')
            ->setParameter('group', $group);

        return $qb;
    }
}
