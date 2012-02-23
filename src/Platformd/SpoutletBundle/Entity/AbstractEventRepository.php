<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Query;
use Doctrine\ORM\EntityRepository;
use DateTime;
use Doctrine\ORM\QueryBuilder;
use Platformd\GiveawayBundle\Entity\Giveaway;

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
        $qb = $this->getBaseQueryBuilder($locale);
        $query = $this->addActiveQuery($qb)
            ->orderBy('e.starts_at', 'DESC')
            ->getQuery()
        ;

        $items = $this->addQueryLimit($query, $limit)->getResult();

        // our hack since Giveaways are special :/
        foreach ($items as $key => $item) {
            // todo - remove this hack - see #18
            if ($item instanceof Giveaway && $item->isDisabled()) {
                unset($items[$key]);
            }
        }

        return $items;
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

    public function findOneBySlug($slug, $locale)
    {
        return $this->findOneBy(array(
            'locale' => $locale,
            'slug'   => $slug,
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
     * Adds the "is active" part of the query by date
     *
     * This allows the starts_at or ends_at to be null, and for that to be "active"
     *
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function addActiveQuery(QueryBuilder $qb)
    {
        $qb
            ->andWhere('e.starts_at < :cut_off OR e.starts_at IS NULL')
            ->andWhere('e.ends_at > :cut_off OR e.ends_at IS NULL')
            ->setParameter('cut_off', new \DateTime())
        ;

        return $qb;
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
