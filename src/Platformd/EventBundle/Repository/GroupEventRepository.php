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
            ->andWhere($alias.'.published = 1')
            ->andWhere($alias.'.approved = 1');
    }
}
