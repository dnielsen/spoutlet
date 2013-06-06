<?php

namespace Platformd\GiveawayBundle\Entity\Repository;

use Platformd\GiveawayBundle\Entity\Giveaway;
use DateTime;
use Platformd\UserBundle\Entity\User;

class GiveawayKeyRepository extends AbstractCodeRepository
{
    public function getTotalForGiveaway(Giveaway $giveaway)
    {
        return (int)$this
            ->createForGiveawayQueryBuilder($giveaway)
            ->select('COUNT(k.id)')
            ->getQuery()
            ->getSingleScalarResult()
            ;
    }

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

    public function getUserAssignedCodeForGiveaway($user, Giveaway $giveaway)
    {
        if (!$user) {
            return null;
        }

        return $this
            ->createQueryBuilder('k')
            ->leftJoin('k.pool', 'p')
            ->andWhere('k.user = :user')
            ->andWhere('p.giveaway = :giveaway')
            ->setParameters(array(
                'user'      => $user,
                'giveaway'  => $giveaway,
            ))
            ->getQuery()
            ->getOneOrNullResult();
    }
    public function getKeyValueByKeyIdAndUserId($keyId, $userId) {

        $result = $this->createQueryBuilder('key')
            ->select('key.value')
            ->leftJoin('key.user', 'user')
            ->andWhere('key.id = :keyId')
            ->andWhere('user.id = :userId')
            ->setParameter('keyId', $keyId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();

        if (!$result) {
            return null;
        }

        return $result[0];
    }

    public function getRegionCountsByDate($from, $to)
    {
        $qb  = $this->createQueryBuilder('k')
            ->select('COUNT(k.id) AS keyCount', 'g.id AS giveawayId', 'g.name AS giveawayName', 'r.name AS regionName')
            ->leftJoin('k.pool','p')
            ->leftJoin('p.giveaway','g')
            ->leftJoin('k.country', 'c')
            ->leftJoin('c.regions', 'r')
            ->addGroupBy('r.name')
            ->addGroupBy('g.name');

        $this->addAssignedQueryBuilder($qb);

        if ($from) {
            $qb->andWhere('k.assignedAt >= :from')
                ->setParameter('from', $from);
        }

        if ($to) {
            $qb->andWhere('k.assignedAt <= :to')
                ->setParameter('to', $to);
        }

        return $qb->getQuery()
            ->getResult();
    }

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
     * Returns the total number of keys for the given array of pools
     */
    public function getTotalUnassignedKeysForPools($pools)
    {
        $ids = array(0);
        foreach ($pools as $pool) {
            array_push($ids, $pool->getId());
        }

        $qb = $this->createQueryBuilder('k');

        return (int)$qb->select('COUNT(k.id)')
            ->leftJoin('k.pool', 'kp')
            ->where('k.user IS NULL')
            ->andWhere('kp.isActive = 1')
            ->andWhere($qb->expr()->in('kp.id', $ids))
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createForGiveawayQueryBuilder(Giveaway $giveaway)
    {
        return $this->createQueryBuilder('k')
            ->leftJoin('k.pool','gkp')
            ->andWhere('gkp.giveaway = :giveaway')
            ->setParameter('giveaway', $giveaway)
        ;
    }
}
