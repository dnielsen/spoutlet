<?php

namespace Platformd\GiveawayBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class DealPoolRepository extends EntityRepository
{
    public function getAllPoolsForDealGivenCountry($deal, $country)
    {
        $qb = $this->createQueryBuilder('pool')
            ->leftJoin('pool.deal', 'deal')
            ->leftJoin('pool.allowedCountries', 'country')
            ->andWhere('deal = :deal')
            ->andWhere('pool.isActive = true')
            ->andWhere('country.code = :countryCode')
            ->setParameter('deal', $deal)
            ->setParameter('countryCode', $country->getCode())
        ;

       return $qb->getQuery()->execute();
    }
}
