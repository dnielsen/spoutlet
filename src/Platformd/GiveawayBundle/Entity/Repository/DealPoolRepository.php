<?php

namespace Platformd\GiveawayBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class DealPoolRepository extends EntityRepository
{
    public function getAllPoolsForDealGivenCountry($deal, $country)
    {
        $qb = $this->createQueryBuilder('pool')
            ->leftJoin('pool.deal', 'deal')
            ->andWhere('deal = :deal')
            ->andWhere('pool.isActive = true')
            ->setParameter('deal', $deal)
        ;

        if ($country != null) {
            $qb->leftJoin('pool.allowedCountries', 'country')
                ->andWhere('country.code = :countryCode')
                ->setParameter('countryCode', $country->getCode());
        }

       return $qb->getQuery()->execute();
    }
}
