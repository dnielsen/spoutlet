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
     * Return current AND upcoming events
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
        $items = $this->removeDisabledGiveaways($items);

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
        $items = $this->createQueryBuilder('e')
            ->andWhere('e.locale = :locale')
            ->andWhere('e.published = :published')
            ->setParameters(array(
                'locale'    => $locale,
                'published' => true,
            ))
            ->orderBy('e.starts_at', 'DESC')
            ->getQuery()
            ->execute()
        ;

        $items = $this->removeDisabledGiveaways($items);

        return $items;
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
     * Adds the "is active" OR upcoming part of the query by date
     *
     * This allows the starts_at or ends_at to be null, and for that to be "active"
     *
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function addActiveQuery(QueryBuilder $qb)
    {
        $qb
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

    /**
     * A hack - see #18
     *
     * @param $abstractEvents
     * @return mixed
     */
    private function removeDisabledGiveaways($abstractEvents)
    {
        foreach ($abstractEvents as $key => $item) {
            // todo - remove this hack - see #18
            if ($item instanceof Giveaway && $item->isDisabled()) {
                unset($abstractEvents[$key]);
            }
        }

        return $abstractEvents;
    }
}
