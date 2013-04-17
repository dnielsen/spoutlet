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

    public function getAssignedForGiveawayByDate(Giveaway $giveaway, $from, $to)
    {
        $qb  = $this->createForGiveawayQueryBuilder($giveaway)
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

    private function createForGiveawayQueryBuilder(Giveaway $giveaway)
    {
        return $this->createQueryBuilder('k')
            ->leftJoin('k.pool','gkp')
            ->andWhere('gkp.giveaway = :giveaway')
            ->setParameter('giveaway', $giveaway)
        ;
    }
}
