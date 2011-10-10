<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * EventRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EventRepository extends EntityRepository
{
    public function getCurrentEvents()
    {
        return $this->createQueryBuilder('e')
            ->where('e.starts_at < :cut_off')
            ->andWhere('e.ends_at > :cut_off')
            ->setParameter('cut_off', new \DateTime())
            ->orderBy('e.starts_at', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getUpcomingEvents()
    {
        return $this->createQueryBuilder('e')
            ->where('e.starts_at > :cut_off')
            ->setParameter('cut_off', new \DateTime())
            ->orderBy('e.starts_at', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}