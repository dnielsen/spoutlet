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
    
    /**
     * Return current events
     * 
     * @param integer $limit
     * @return array
     */
    public function getCurrentEvents($locale, $limit = null)
    {
        $query = $this->getBaseQueryBuilder($locale)
            ->andWhere('e.ends_at > :cut_off')
            ->setParameter('cut_off', new \DateTime())
            ->orderBy('e.starts_at', 'ASC')
            ->getQuery();

        return $this->addQueryLimit($query, $limit)->getResult();
    }

    /**
     * Return past events
     * 
     * @param integer $limit
     * @return array
     */
    public function getPastEvents($locale, $limit = null)
    {
        $query = $this->getBaseQueryBuilder($locale)
            ->andWhere('e.ends_at < :cut_off')
            ->setParameter('cut_off', new \DateTime())
            ->orderBy('e.starts_at', 'ASC')
            ->getQuery();
        
        return $this->addQueryLimit($query, $limit)->getResult();
    }
    
    /**
     * Return a query builder instance that should be used for frontend request
     * basically, it only adds a criteria to retrieve only published events
     * 
     * @param String $alias
     * @return Doctrine\ORM\QueryBuilder
     */
    public function getBaseQueryBuilder($locale, $alias = 'e')
    {
        
        return $this->createQueryBuilder($alias)
            ->where($alias.'.published = 1')
            ->andWhere($alias.'.locale = :locale')
            ->setParameter('locale', $locale);
    }
    
    /**
     * Retrieve published events
     *
     * @return array
     */
    public function findPublished($locale) 
    {
        
        return $this->findBy(array(
            'locale'    => $locale,
            'published' => true
        ));
    }

    private function addQueryLimit(Query $query, $limit)
    {
        if  (null === $limit) {
            return $query;
        }

        return $query->setMaxResults($limit);
    }
}
