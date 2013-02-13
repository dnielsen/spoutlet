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
            ->andWhere('e.endsAt > :now')
            ->andWhere('e.published = 1')
            ->orderBy('e.startsAt')
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
            ->andWhere('e.endsAt < :now')
            ->andWhere('e.published = 1')
            ->orderBy('e.endsAt', 'DESC')
            ->setParameter('now', new \DateTime('now'));

        $this->addActiveClauses($qb);

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    private function getBaseGroupQueryBuilder(Group $group, $alias = 'e')
    {
        $qb = $this->createQueryBuilder($alias)
            ->andWhere($alias.'.group = :group')
            ->setParameter('group', $group);

        return $qb;
    }

    public function getPendingApprovalEventsForGroup(Group $group)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e', 'g')
            ->leftJoin('e.group', 'g')
            ->where('e.approved = false')
            ->andWhere('e.group = :group')
            ->setParameter('group', $group)
            ->orderBy('e.createdAt', 'DESC')
        ;

        return $qb->getQuery()->getResult();
    }

    protected function addActiveClauses($qb, $alias='e')
    {
        return $qb->andWhere($alias.'.deleted = 0')
            ->andWhere($alias.'.approved = 1');
    }
}
