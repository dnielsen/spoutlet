<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * HomepageBannerRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class HomepageBannerRepository extends EntityRepository
{
    public function findAllWithoutLocaleOrderedByNewest()
    {
        return $this->createQueryBuilder('h')
            ->orderBy('h.created', 'DESC')
            ->getQuery()
            ->execute()
            ;
    }

    /**
     *
     */
    public function findForLocale($locale)
    {
        
        return $this
            ->createQueryBuilder('h')
            ->where('h.locale = ?0')
            ->orderBy('h.position', 'ASC')
            ->getQuery()
            ->execute(array($locale));
    }
}