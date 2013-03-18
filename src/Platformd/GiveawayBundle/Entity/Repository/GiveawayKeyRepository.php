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

    /**
     * Returns top 4 (by default) expired giveaways with 0 keys
     * @return \Platformd\GiveawayBundle\Entity\Giveaway[]
     */
    public function findExpiredWithZeroKeysForSite($site, $limit=5)
    {
        return $this->createQueryBuilder('k')
            ->select('g.id, COUNT(k.id) as keyCount')
            ->leftJoin('k.pool', 'gkp')
            ->leftJoin('gkp.giveaway', 'g', 'WITH', 'g.featured != 1 AND g.status != :flag')
            ->leftJoin('g.sites', 's')
            ->andWhere(is_string($site) ? 's.name = :site' : 's = :site')
            ->groupBy('g.id')
            ->having('keyCount = 0')
            ->setParameter('flag', 'disabled')
            ->setParameter('site', $site)
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();
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
