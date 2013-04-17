<?php

namespace Platformd\GiveawayBundle\Entity\Repository;

use Platformd\GiveawayBundle\Entity\Repository\AbstractCodeRepository;
use Platformd\GiveawayBundle\Entity\Deal;
use DateTime;
use Platformd\UserBundle\Entity\User;

class DealCodeRepository extends AbstractCodeRepository
{
    public function getTotalForDeal(Deal $deal)
    {
        return (int)$this
            ->createForDealQueryBuilder($deal)
            ->select('COUNT(k.id)')
            ->getQuery()
            ->getSingleScalarResult()
            ;
    }

    public function getTotalAvailableForDeal(Deal $deal)
    {
        return (int)$this
            ->createForDealQueryBuilder($deal)
            ->select('COUNT(k.id)')
            ->andWhere('k.user is NULL')
            ->getQuery()
            ->getSingleScalarResult()
            ;
    }

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

    public function getAssignedForDealByDate(Deal $deal, $from, $to)
    {
        $qb  = $this->createForDealQueryBuilder($deal)
            ->select('k.id', 'k.ipAddress');

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

    public function getAssignedForDealAndSite(Deal $deal, $site, $from, $to)
    {
        $qb  = $this->createForDealQueryBuilder($deal);
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
            $to->setTime(23,59,59);
            $qb->andWhere('k.assignedAt <= :to')
                ->setParameter('to', $to)
            ;
        }

        return (int) $qb
            ->getQuery()
            ->getSingleScalarResult()
            ;
    }

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

    private function createForDealQueryBuilder(Deal $deal)
    {
        return $this->createQueryBuilder('k')
            ->leftJoin('k.pool','gkp')
            ->andWhere('gkp.deal = :deal')
            ->setParameter('deal', $deal)
        ;
    }
}
