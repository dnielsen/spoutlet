<?php

namespace Platformd\IdeaBundle\Entity;

use Doctrine\ORM\EntityRepository;

use Platformd\UserBundle\Entity\User;

/**
 * WatchedEventMappingRepository
 */
class WatchedEventMappingRepository extends EntityRepository
{
    public function getAllGlobalEventsUserIsWatching(User $user)
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.global_event', 'e')
            ->select('e.id')
            ->andWhere('m.user = :user')
            ->setParameter('user', $user);

        return $qb->getQuery()->getResult();
    }

    public function getAllGroupEventsUserIsWatching(User $user)
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.group_event', 'e')
            ->select('e.id')
            ->andWhere('m.user = :user')
            ->setParameter('user', $user);

        return $qb->getQuery()->getResult();
    }
}
