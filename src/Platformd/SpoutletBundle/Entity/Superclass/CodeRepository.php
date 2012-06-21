<?php

namespace Platformd\SpoutletBundle\Entity\Superclass;

use Doctrine\ORM\EntityRepository;
use Platformd\SpoutletBundle\Entity\Superclass\Pool;
use Platformd\UserBundle\Entity\User;
use Platformd\GiveawayBundle\Util\KeyCounterUtil;
use Platformd\GiveawayBundle\Entity\Giveaway;
use Doctrine\ORM\QueryBuilder;
use DateTime;

/**
 * Base repository for any entities that extend from the Code mapped superclass
 */
abstract class CodeRepository extends EntityRepository
{
    /**
     * Returns the number of keys that should "appear" to be available based on:
     *
     * Its kind of a strange requirement .. If a giveaway includes 50,000 keys, they may only want to
     * show 10,000, then when it gets down to 1000 they will reset it to 10,000 until all 50,000 are
     * given away. The 10,000 is the upper limit and the 1000 is the lower limit. I know, strange!
     *
     * @param \Platformd\SpoutletBundle\Entity\Superclass\Pool $pool
     * @return int
     */
    public function getUnassignedForPoolForDisplay(Pool $pool = null)
    {
        // make sure this pool is active
        if (!$this->shouldPoolExposeKeys($pool)) {
            return 0;
        }

        // offload the work to something that we can easily unit test
        $util = new KeyCounterUtil();

        return $util->getTrueDisplayCount(
            $this->getTotalForPool($pool),
            $this->getUnassignedForPool($pool),
            $pool->getLowerLimit(),
            $pool->getUpperLimit()
        );
    }

    /**
     * @param \Platformd\SpoutletBundle\Entity\Superclass\Pool $pool
     * @return \Platformd\SpoutletBundle\Entity\Superclass\Code|null
     */
    public function getUnassignedKey(Pool $pool = null)
    {
        // make sure this pool is active
        if (!$this->shouldPoolExposeKeys($pool)) {
            return null;
        }

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
     * Returns the TRUE number of keys that have been assigned for the given pool
     *
     * @param \Platformd\SpoutletBundle\Entity\Superclass\Pool $pool
     * @return int
     */
    public function getAssignedForPool(Pool $pool)
    {
        return (int)$this
            ->createQueryBuilder('k')
            ->select('COUNT(k.id)')
            ->where('k.user IS NOT NULL')
            ->andWhere('k.pool = :pool')
            ->setParameter('pool', $pool->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Returns the TRUE number of keys that have NOTbeen assigned for the given pool
     *
     * @param \Platformd\SpoutletBundle\Entity\Superclass\Pool $pool
     * @return int
     */
    public function getUnassignedForPool(Pool $pool)
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
     * @param \Platformd\SpoutletBundle\Entity\Superclass\Pool $pool
     * @return int
     */
    public function getTotalForPool(Pool $pool)
    {
        return (int)$this
            ->createQueryBuilder('k')
            ->select('COUNT(k.id)')
            ->andWhere('k.pool = :pool')
            ->setParameter('pool', $pool->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param $id
     * @param \Platformd\UserBundle\Entity\User $user
     * @return \Platformd\SpoutletBundle\Entity\Superclass\Code
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

    /**
     * @param \Platformd\UserBundle\Entity\User $user
     * @return array
     */
    public function findAssignedToUser(User $user)
    {
        return $this
            ->createQueryBuilder('k')
            ->where('k.user = :user')
            ->setParameters(array(
            'user'  => $user,
        ))
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns whether or not the given IP should be given more keys
     *
     * @param string $ip
     * @param \Platformd\SpoutletBundle\Entity\Superclass\Pool $pool
     * @return bool
     */
    public function canIpHaveMoreKeys($ip, Pool $pool)
    {
        // if we have a zero max keys, then there is no limit
        if ($pool->getMaxKeysPerIp() <=0 ) {
            return true;
        }

        $currentCount = (int)$this
            ->createQueryBuilder('k')
            ->select('COUNT(k.id)')
            ->andWhere('k.pool = :pool')
            ->setParameter('pool', $pool->getId())
            ->andWhere('k.ipAddress = :ip')
            ->setParameter('ip', $ip)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $currentCount < $pool->getMaxKeysPerIp();
    }

    /**
     * Whether or not a given pool should "appear" to have zero available
     * keys even if it has more
     *
     * @param \Platformd\SpoutletBundle\Entity\Superclass\Pool $pool
     * @return bool
     */
    protected function shouldPoolExposeKeys(Pool $pool = null)
    {
        // no pool?? get outta here!
        if (!$pool) {
            return false;
        }

        // same thing - if the pool is inactive, then definitely don't expose
        if (!$pool->isTotallyActive()) {
            return false;
        }

        return true;
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function addAssignedQueryBuilder(QueryBuilder $qb)
    {
        return $qb->andWhere('k.user IS NOT NULL');
    }
}
