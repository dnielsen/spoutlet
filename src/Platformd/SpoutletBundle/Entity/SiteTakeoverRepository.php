<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * SiteTakeoverRepository
 *
 * Add your own custom
 * repository methods below.
 */
class SiteTakeoverRepository extends EntityRepository
{
    public function findAllForSiteSoonestFirst($site)
    {
        return $this->createSiteQueryBuilder($site)
            ->addOrderBy('t.startsAt', 'ASC')
            ->getQuery()
            ->execute()
        ;
    }

    public function findOverlappingTakeovers($takeover)
    {
        $sites = array();

        foreach ($takeover->getSites() as $site) {
            $sites[] = $site->getId();
        }

        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.sites', 's')
            ->andWhere('s.id IN (:sites)')
            ->andWhere('(t.startsAt >= :startsAt AND t.startsAt <= :endsAt) OR (t.endsAt <= :endsAt AND t.endsAt >= :startsAt)')
            ->andWhere('t.id <> :id')
            ->setParameters(array(
                'startsAt'  => $takeover->getStartsAt(),
                'endsAt'    => $takeover->getEndsAt(),
                'sites'     => $sites,
                'id'        => $takeover->getId(),
            ))
            ->groupBy('t.id');

            return $qb->getQuery()->execute();
    }

    private function createSiteQueryBuilder($site)
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.sites', 's');

        if (is_string($site)) {
            $qb->andWhere('s.name = :site')
                ->setParameter('site', $site);
        } else {
            $qb->andWhere('s = :site')
            ->setParameter('site', $site);
        }

        return $qb;
    }

    public function getCurrentTakeover($site)
    {
        return $this->createSiteQueryBuilder($site)
            ->andWhere('t.startsAt <= :now')
            ->andWhere('t.endsAt > :now')
            ->setParameter('now', new \DateTime('now'))
            ->setMaxResults(1)
            ->orderBy('t.startsAt', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
