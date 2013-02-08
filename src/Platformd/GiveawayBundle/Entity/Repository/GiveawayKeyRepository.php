<?php

namespace Platformd\GiveawayBundle\Entity\Repository;

use Platformd\SpoutletBundle\Entity\Superclass\CodeRepository;
use Platformd\GiveawayBundle\Entity\Giveaway;
use DateTime;
use Platformd\UserBundle\Entity\User;

/**
 * GiveawayKey Repository
 */
class GiveawayKeyRepository extends CodeRepository
{
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
    public function getAssignedForGiveawayAndSite(Giveaway $giveaway, $site, $from, $to)
    {
        $qb  = $this->createForGiveawayQueryBuilder($giveaway);
        $this->addAssignedQueryBuilder($qb);

        $qb->select('COUNT(k.id)')
            ->andWhere('k.assignedSite = :site')
            ->setParameter('site', $site)
        ;

        if ($from) {
            $qb->andWhere('k.assignedAt >= :from')
                ->setParameter('from', $from)
            ;
        }

        if ($to) {
            $qb->andWhere('k.assignedAt <= :to')
                ->setParameter('to', $to)
            ;
        }

        return (int) $qb
            ->getQuery()
            ->getSingleScalarResult()
            ;
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
}
