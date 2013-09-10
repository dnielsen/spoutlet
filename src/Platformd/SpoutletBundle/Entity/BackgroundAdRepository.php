<?php

namespace Platformd\SpoutletBundle\Entity;

use Platformd\SpoutletBundle\Entity\BackgroundAd;
use Doctrine\ORM\EntityRepository;
use Platformd\SpoutletBundle\Entity\Site;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\NonUniqueResultException;

class BackgroundAdRepository extends EntityRepository
{
    public function findBySite($site)
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.adSites', 'ads')
            ->andWhere('ads.site = :site')
            ->setParameter('site', $site)
            ->getQuery()
            ->execute()
        ;
    }

    public function hasSameTimeForSites(BackgroundAd $ad)
    {
        $siteIds = $ad->getSiteIds();
        if (empty($siteIds) || !$ad->getDateStart() || !$ad->getDateEnd()) {
            return false;
        }

        $qb = $this->createQueryBuilder('b');
        $qb
            ->select('COUNT(b.id)')
            ->leftJoin('b.adSites', 'ads')
            ->andWhere(
                $qb->expr()->orX(
                    'b.dateStart BETWEEN :dateStart AND :dateEnd', // start date in existing period
                    'b.dateEnd BETWEEN :dateStart AND :dateEnd', // end date in existing period
                    'b.dateStart <= :dateStart AND b.dateEnd >= :dateEnd' // dates englobs existing period
                )
            )
            ->andWhere('ads.site IN (:sites)')
            ->setParameter('dateStart', $ad->getDateStartTimezoned())
            ->setParameter('dateEnd', $ad->getDateEndTimezoned())
            ->setParameter('sites', array_values($siteIds))
        ;
        if ($ad->getId()) {
            $qb
                ->andWhere('b.id != :id')
                ->setParameter('id', $ad->getId())
            ;
        }

        return $qb
            ->getQuery()
            ->getSingleScalarResult() >= 1;
        ;
    }

    public function getCurrentBackgroundAdSite(Site $site = null, $timezone = 'UTC')
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('ads, b, f')
            ->from('SpoutletBundle:BackgroundAdSite', 'ads')
            ->innerJoin('ads.ad', 'b')
            ->leftJoin('b.file', 'f')
            ->andWhere('b.dateStart <= :dateStart AND b.dateEnd >= :dateEnd')
            ->andWhere('ads.site = :site')
            ->andWhere('b.isPublished = true')
            ->orderBy('b.dateStart', 'DESC')
            ->setMaxResults(1)
            ->setParameter('dateStart', new \DateTime('now', new \DateTimeZone($timezone)))
            ->setParameter('dateEnd', new \DateTime('now', new \DateTimeZone($timezone)))
            ->setParameter('site', $site)
        ;

        try {
            $adSite = $qb
                ->getQuery()
                ->getSingleResult();
            ;

            return $adSite;
        }
        catch (NoResultException $e) {
            return;
        }
    }
}

