<?php

namespace Platformd\HtmlWidgetBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Platformd\SpoutletBundle\Entity\Site;

class HtmlWidgetRepository extends EntityRepository
{
    public function findAllForSite(Site $site)
    {
        $qb = $this->createQueryBuilder('w')
            ->leftJoin('w.sites', 's')
            ->andWhere('s = :site')
            ->setParameter('site', $site)
        ;

        return $qb->getQuery()->execute();
    }

    public function findAllBy(array $criteria)
    {
        $qb = $this->createQueryBuilder('w');

        foreach ($criteria as $key => $value) {
            if ($key == 'sites') {
                if (count($value) > 0) {
                    $qb->leftJoin('w.sites', 's')
                        ->andWhere('s in (:siteList)')
                        ->setParameter('siteList', $value);
                }
            } else {
                $qb->andWhere('w.'.$key.' = :value')
                    ->setParameter('value', $value);
            }
        }

        try {
            return $qb->getQuery()->execute();
        } catch (\Exception $e) {
            // catching exception thrown most likely by an invalid criterion being provided
            die('Exception thrown when trying to call HtmlWidgetRepository->findCountBy. Check the criteria passed is valid. Error said: '.$e->getMessage());
        }
    }
}
