<?php

namespace Platformd\GiveawayBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Platformd\GiveawayBundle\Entity\GiveawayPool;
use Platformd\UserBundle\Entity\User;

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
     * @param \Platformd\GiveawayBundle\Entity\GiveawayPool $pool
     * @return \Platformd\GiveawayBundle\Entity\GiveawayKey|null
     */
    public function getUnassignedKey(GiveawayPool $pool) 
    {
        return $this
            ->createQueryBuilder('k')
            ->where('k.user IS NULL')
            ->andWhere('k.pool = :pool')
            ->setMaxResults(1)
            ->setParameter('pool', $pool->getId())
            ->getQuery()
            ->getOneOrNullResult();
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

    /**
     * @param $id
     * @param \Platformd\UserBundle\Entity\User $user
     * @return \Platformd\GiveawayBundle\Entity\GiveawayKey
     */
    public function findOneByIdAndUser($id, User $user)
    {
        return $this
            ->createQueryBuilder('k')
            ->where('k.user = :user')
            ->andWhere('k.id = :id')
            ->setParameters(array(
                'id'    => $id,
                'user'  => $user,
            ))
            ->getQuery()
            ->getOneOrNullResult();
    }
}
