<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Platformd\SpoutletBundle\Entity\Country;
use Platformd\SpoutletBundle\Entity\Site;
use Platformd\SpoutletBundle\Location\Ip2LocationRecord;

class RegionRepository extends EntityRepository
{
    public function findSiteByCountry($country)
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r')
            ->leftJoin('r.countries', 'c')
            ->where('c = :country')
            ->andWhere('r.site IS NOT NULL')
            ->setParameter('country', $country);

        $result = $qb->getQuery()
            ->getOneOrNullResult();

        return $result ? $result->getSite() : null;
    }

    public function findRegionNamesForCountry($country)
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r.name')
            ->leftJoin('r.countries', 'c');

        if ($country instanceof Country) {
            $qb->where('c = :country')
                ->setParameter('country', $country);
        } else {
             $qb->where('c.code = :country')
                ->setParameter('country', $country);
        }

        return $qb->getQuery()
            ->getResult();
    }

    public function findRegionForSite(Site $site)
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.site', 's')
            ->where('s = :site')
            ->setParameter('site', $site);

        return $qb->getQuery()
            ->getOneOrNullResult();
    }

    public function getRegionsForCountries()
    {
        $result = $this->createQueryBuilder('r')
            ->select('r, c')
            ->leftJoin('r.countries', 'c')
            ->getQuery()
            ->getResult();

        $regions = array();

        foreach ($result as $region) {
            foreach ($region->getCountries() as $country) {
                $regions[$country->getCode()][] = $region->getName();
            }
        }

        return $regions;
    }
}
