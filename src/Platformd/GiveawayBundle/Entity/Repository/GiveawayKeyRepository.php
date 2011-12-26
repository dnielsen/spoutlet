<?php

namespace Platformd\GiveawayBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Platformd\GiveawayBundle\Entity\GiveawayPool;
use Platformd\UserBundle\Entity\User;
use Platformd\GiveawayBundle\Util\KeyCounterUtil;
use Platformd\GiveawayBundle\Entity\Giveaway;
use Doctrine\ORM\QueryBuilder;
use DateTime;

/**
 * GiveawayKey  Repository
 */
class GiveawayKeyRepository extends EntityRepository
{
    /**
     * Returns the number of keys that should "appear" to be available based on:
     *
     * Its kind of a strange requirement .. If a giveaway includes 50,000 keys, they may only want to
     * show 10,000, then when it gets down to 1000 they will reset it to 10,000 until all 50,000 are
     * given away. The 10,000 is the upper limit and the 1000 is the lower limit. I know, strange!
     *
     * @param \Platformd\GiveawayBundle\Entity\GiveawayPool $pool
     * @return int
     */
    public function getUnassignedForPoolForDisplay(GiveawayPool $pool = null)
    {
        // make sure this pool and giveaway are active
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
     * @param \Platformd\GiveawayBundle\Entity\GiveawayPool $pool
     * @return \Platformd\GiveawayBundle\Entity\GiveawayKey|null
     */
    public function getUnassignedKey(GiveawayPool $pool = null)
    {
        // make sure this pool and giveaway are active
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
     * @param \Platformd\GiveawayBundle\Entity\GiveawayPool $pool
     * @return int
     */
    public function getAssignedForPool(GiveawayPool $pool)
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
     * @param \Platformd\GiveawayBundle\Entity\GiveawayPool $pool
     * @return int
     */
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
     * @param \Platformd\GiveawayBundle\Entity\GiveawayPool $pool
     * @return int
     */
    public function getTotalForPool(GiveawayPool $pool)
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
     * Returns the total number for keys for all pools across a giveaway
     *
     * @param \Platformd\GiveawayBundle\Entity\Giveaway $giveaway
     * @return integer
     */
    public function getTotalForGiveaway(Giveaway $giveaway)
    {
        return (int)$this
            ->createForGiveawayQueryBuilder($giveaway)
            ->select('COUNT(k.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Returns the total number of assigned keys across all pools of a giveaway
     *
     * @param \Platformd\GiveawayBundle\Entity\Giveaway $giveaway
     * @return integer
     */
    public function getAssignedForGiveaway(Giveaway $giveaway)
    {
        $qb  = $this->createForGiveawayQueryBuilder($giveaway);
        $this->addAssignedQueryBuilder($qb);

        return (int) $qb
            ->select('COUNT(k.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Returns the total number of assigned keys for the given giveaway
     * and site combination
     *
     *
     * @param \Platformd\GiveawayBundle\Entity\Giveaway $giveaway
     * @param $site
     * @param \DateTime $since
     * @return integer
     */
    public function getAssignedForGiveawayAndSite(Giveaway $giveaway, $site, DateTime $since = null)
    {
        $qb  = $this->createForGiveawayQueryBuilder($giveaway);
        $this->addAssignedQueryBuilder($qb);

        $qb->select('COUNT(k.id)')
            ->andWhere('k.assignedSite = :site')
            ->setParameter('site', $site)
        ;

        if ($since) {
            $qb->andWhere('k.assignedAt >= :since')
                ->setParameter('since', $since)
            ;
        }

        return (int) $qb
            ->getQuery()
            ->getSingleScalarResult()
        ;
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
     * @param \Platformd\GiveawayBundle\Entity\GiveawayPool $pool
     * @return bool
     */
    public function canIpHaveMoreKeys($ip, GiveawayPool $pool)
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
     * Determines whether or not the given user has a key for any pool from
     * the given Giveaway
     *
     * @param \Platformd\UserBundle\Entity\User $user
     * @param \Platformd\GiveawayBundle\Entity\Giveaway $giveaway
     * @return bool
     */
    public function doesUserHaveKeyForGiveaway(User $user, Giveaway $giveaway)
    {
        $count = (int)$this
            ->createQueryBuilder('k')
            ->select('COUNT(k.id)')
            ->leftJoin('k.pool', 'p')
            ->andWhere('k.user = :user')
            ->andWhere('p.giveaway = :giveaway')
            ->setParameters(array(
                'user'      => $user,
                'giveaway'  => $giveaway,
            ))
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Whether or not a given pool should "appear" to have zero available
     * keys even if it has more
     *
     * @param \Platformd\GiveawayBundle\Entity\GiveawayPool $pool
     * @return bool
     */
    protected function shouldPoolExposeKeys(GiveawayPool $pool = null)
    {
        // no pool?? get outta here!
        if (!$pool) {
            return false;
        }

        // unless the giveaway is active, we don't expose keys
        if (!$pool->getGiveaway()->isActive()) {
            return false;
        }

        // same thing - if the pool is inactive, then definitely don't expose
        if (!$pool->getIsActive()) {
            return false;
        }

        return true;
    }

    /**
     * @param \Platformd\GiveawayBundle\Entity\Giveaway $giveaway
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function createForGiveawayQueryBuilder(Giveaway $giveaway)
    {
        return $this->createQueryBuilder('k')
            ->leftJoin('k.pool','gkp')
            ->andWhere('gkp.giveaway = :giveaway')
            ->setParameter('giveaway', $giveaway)
        ;
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function addAssignedQueryBuilder(QueryBuilder $qb)
    {
        return $qb->andWhere('k.user IS NOT NULL');
    }
}
