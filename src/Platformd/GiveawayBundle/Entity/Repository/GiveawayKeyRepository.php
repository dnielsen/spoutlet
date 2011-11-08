<?php

namespace Platformd\GiveawayBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Platformd\GiveawayBundle\Entity\GiveawayPool;

/**
 * GiveawayKey  Repository
 */
class GiveawayKeyRepository extends EntityRepository
{
    /**
     * Returns the number of keys that have been assigned for the given pool
     *
     * @todo
     * @param \Platformd\GiveawayBundle\Entity\GiveawayPool $pool
     * @return int
     */
    public function getAssignedForPool(GiveawayPool $pool)
    {
        return 0;
    }

    public function getUnassignedForPool(GiveawayPool $pool)
    {
        return (int)$this
            ->createQueryBuilder('k')
            ->select('COUNT(k.id)')
            ->where('k.user IS NULL')
            ->andWhere('k.pool = :pool')
            ->setParameter('pool', $pool->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Returns the total number of keys for the given pool
     *
     * @todo
     * @param \Platformd\GiveawayBundle\Entity\GiveawayPool $pool
     * @return int
     */
    public function getTotalForPool(GiveawayPool $pool)
    {
        return 10000;
    }
}
