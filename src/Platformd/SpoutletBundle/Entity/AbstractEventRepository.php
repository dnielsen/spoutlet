<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Query;
use Doctrine\ORM\EntityRepository;

/**
 * Repository for the base, abstract "events"
 */
class AbstractEventRepository extends EntityRepository
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

    public function findOnePublishedBySlug($slug, $locale)
    {
        return $this->findOneBy(array(
            'locale' => $locale,
            'slug'   => $slug,
            'published' => true,
        ));
    }

    public function findAllWithoutLocaleOrderedByNewest()
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.created', 'DESC')
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * Return a query builder instance that should be used for frontend request
     * basically, it only adds a criteria to retrieve only published events
     *
     * @param String $alias
     * @return Doctrine\ORM\QueryBuilder
     */
    private function getBaseQueryBuilder($locale, $alias = 'e')
    {

        return $this->createQueryBuilder($alias)
            ->where($alias.'.published = 1')
            ->andWhere($alias.'.locale = :locale')
            ->setParameter('locale', $locale);
    }

    private function addQueryLimit(Query $query, $limit)
    {
        if  (null === $limit) {
            return $query;
        }

        return $query->setMaxResults($limit);
    }
}
