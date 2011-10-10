<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Query;
use Doctrine\ORM\EntityRepository;

/**
 * EventRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EventRepository extends EntityRepository
{
    public function getCurrentEvents($limit = null)
    {
        $query = $this->createQueryBuilder('e')
            ->where('e.ends_at > :cut_off')
            ->setParameter('cut_off', new \DateTime())
            ->orderBy('e.starts_at', 'ASC')
            ->getQuery();

        return $this->addQueryLimit($query, $limit)->getResult();
    }

    public function getPastEvents($limit = null)
    {
        $query = $this->createQueryBuilder('e')
            ->where('e.ends_at < :cut_off')
            ->setParameter('cut_off', new \DateTime())
            ->orderBy('e.starts_at', 'ASC')
            ->getQuery();
        
        return $this->addQueryLimit($query, $limit)->getResult();
    }

    private function addQueryLimit(Query $query, $limit)
    {
        if  (null === $limit) {
            return $query;
        }

        return $query->setMaxResults($limit);
    }
}