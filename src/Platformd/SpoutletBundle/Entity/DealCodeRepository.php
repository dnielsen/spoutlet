<?php

namespace Platformd\SpoutletBundle\Entity;

use Platformd\SpoutletBundle\Entity\Superclass\CodeRepository;
use Platformd\SpoutletBundle\Entity\Deal;
use DateTime;
use Platformd\UserBundle\Entity\User;

/**
 * DealCode Repository
 */
class DealCodeRepository extends CodeRepository
{
    /**
     * Returns the total number for codes for all pools across a deal
     *
     * @param \Platformd\SpoutletBundle\Entity\Deal $deal
     * @return integer
     */
    public function getTotalForDeal(Deal $deal)
    {
        return (int)$this
            ->createForDealQueryBuilder($deal)
            ->select('COUNT(k.id)')
            ->getQuery()
            ->getSingleScalarResult()
            ;
    }

    /**
     * Returns the total number of assigned codes across all pools of a deal
     *
     * @param \Platformd\SpoutletBundle\Entity\Deal $deal
     * @return integer
     */
    public function getAssignedForDeal(Deal $deal)
    {
        $qb  = $this->createForDealQueryBuilder($deal);
        $this->addAssignedQueryBuilder($qb);

        return (int) $qb
            ->select('COUNT(k.id)')
            ->getQuery()
            ->getSingleScalarResult()
            ;
    }

    /**
     * Returns the total number of assigned codes for the given deal
     * and site combination
     *
     *
     * @param \Platformd\SpoutletBundle\Entity\Deal $deal
     * @param $site
     * @param \DateTime $since
     * @return integer
     */
    public function getAssignedForDealAndSite(Deal $deal, $site, DateTime $since = null)
    {
        $qb  = $this->createForDealQueryBuilder($deal);
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
     * Determines whether or not the given user has a code for any pool from
     * the given Deal
     *
     * @param \Platformd\UserBundle\Entity\User $user
     * @param \Platformd\SpoutletBundle\Entity\Deal $deal
     * @return bool
     */
    public function doesUserHaveCodeForDeal(User $user, Deal $deal)
    {
        $count = (int)$this
            ->createQueryBuilder('k')
            ->select('COUNT(k.id)')
            ->leftJoin('k.pool', 'p')
            ->andWhere('k.user = :user')
            ->andWhere('p.deal = :deal')
            ->setParameters(array(
            'user'      => $user,
            'deal'  => $deal,
        ))
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

     /**
     * Retrieves the deal code information for a user and deal
     *
     * @param \Platformd\UserBundle\Entity\User $user
     * @param \Platformd\SpoutletBundle\Entity\Deal $deal
     * @return \Platormd\SpoutletBundle\Entity\DealCode
     */
    public function getUserAssignedCodeForDeal(User $user, Deal $deal)
    {
        return $this
            ->createQueryBuilder('k')
            ->leftJoin('k.pool', 'p')
            ->andWhere('k.user = :user')
            ->andWhere('p.deal = :deal')
            ->setParameters(array(
                'user'      => $user,
                'deal'  => $deal,
            ))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retrieves all deal codes a user
     *
     * @param \Platformd\UserBundle\Entity\User $user
     * @return \Platormd\SpoutletBundle\Entity\DealCode[]
     */
    public function getUserAssignedCodes(User $user)
    {
        return $this
            ->createQueryBuilder('k')
            ->leftJoin('k.pool','gkp')
            ->andWhere('k.user = :user')
            ->setParameters(array('user' => $user))
            ->getQuery()
            ->execute();
    }

    /**
     * @param \Platformd\SpoutletBundle\Entity\Deal $deal
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function createForDealQueryBuilder(Deal $deal)
    {
        return $this->createQueryBuilder('k')
            ->leftJoin('k.pool','gkp')
            ->andWhere('gkp.deal = :deal')
            ->setParameter('deal', $deal)
        ;
    }
}
