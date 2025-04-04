<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * SiteRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SiteRepository extends EntityRepository
{
    public function findOneByFullDomain($fullDomain=null)
    {
        if ($fullDomain) {
            $site = $this->createQueryBuilder('s')
                ->andWhere('s.fullDomain = :fullDomain')
                ->setParameter('fullDomain', $fullDomain)
                ->getQuery()
                ->getOneOrNullResult();

            return $site;
        }

        // Required for instances where e.g. GiveawayListener asks for current site when a giveaway is loaded.
        // The default repo function will not work when called from siteUtil when calling from CLI because the current
        // site has not been set by an onKernelRequest call

        $backupResult = $this->createQueryBuilder('s')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $backupResult;
    }

    public function findAllWithIdNotIn($ids)
    {
        $qb = $this->createQueryBuilder('s');

        if (count($ids) > 0) {
            $qb->andWhere('s.id NOT IN (:ids)')
                ->setParameter('ids', $ids);
        }

        return $qb->getQuery()
            ->execute();
    }
}
