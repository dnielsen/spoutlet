<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class DealRepository extends EntityRepository
{
    /**
     * Includes unpublished Deals
     *
     * @param string $site
     * @return \Platformd\SpoutletBundle\Entity\Deal[]
     */
    public function findAllForSiteNewestFirst($site)
    {
        return $this->createSiteQueryBuilder($site, false)
            ->addOrderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @param string $slug
     * @param string $site
     * @return \Platformd\SpoutletBundle\Entity\Deal
     */
    public function findOneBySlugForSite($slug, $site)
    {
        return $this->createSiteQueryBuilder($site)
            ->andWhere('d.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @param string $name
     * @param string $site
     * @return \Platformd\SpoutletBundle\Entity\Deal
     */
    public function findOneByNameForSite($name, $site)
    {
        return $this->createSiteQueryBuilder($site)
            ->andWhere('d.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Returns the "featured" deals
     *
     * @param $site
     * @return \Platformd\SpoutletBundle\Entity\Deal[]
     */
    public function findFeaturedDealsForSite($site)
    {
        return $this->createSiteQueryBuilder($site)
            ->addOrderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->setMaxResults(4)
            ->execute()
        ;
    }

    /**
     * Finds all active deals, except for those "featured", which are passed in
     *
     * @param string $site
     * @param array $featuredDeals
     * @return \Platformd\SpoutletBundle\Entity\Deal[]
     */
    public function findAllActiveNonFeatureDealsForSite($site, array $featuredDeals)
    {
        $qb = $this->createSiteQueryBuilder($site);
        $this->addActiveQueryBuilder($qb);

        $qb->andWhere('d.id NOT IN :featuredIds')
            ->setParameter('featuredIds', self::objectsToIdsString($featuredDeals))
        ;

        return $qb->getQuery()
            ->execute()
        ;
    }

    /**
     * @param $site
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function createSiteQueryBuilder($site, $returnOnlyPublished = true)
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.dealLocales', 'dl')
            ->andWhere('dl.locale = :site')
            ->setParameter('site', $site)
        ;

        if ($returnOnlyPublished) {
            $this->addPublishedQueryBuilder($qb);
        }

        return $qb;
    }

    /**
     * Adds the "is published" parts to a query
     *
     * @param null|QueryBuilder $qb
     * @return \Doctrine\ORM\QueryBuilder|null|QueryBuilder
     */
    private function addPublishedQueryBuilder(QueryBuilder $qb = null)
    {
        if ($qb === null) {
            $qb = $this->createQueryBuilder('d');
        }

        $qb->andWhere('d.status = :publishedStatus')
            ->setParameter('publishedStatus', Deal::STATUS_PUBLISHED)
        ;

        return $qb;
    }

    /**
     * Adds a query builder to return only "active" deals:
     *      * deals that have already started
     *      * deals that have not expired
     *
     * This allows both the startsAt and endsAt to be null, which implies
     * that the Deal is active
     *
     * @param QueryBuilder|null $qb
     * @return \Doctrine\ORM\QueryBuilder|null
     */
    private function addActiveQueryBuilder(QueryBuilder $qb = null)
    {
        if ($qb === null) {
            $qb = $this->createQueryBuilder('d');
        }

        $qb->andWhere('d.startsAt < :now OR d.startsAt IS NULL');
        $qb->andWhere('d.endsAt > :now OR d.ends_at IS NULL');

        return $qb;
    }

    /**
     * Utility function that takes an array of entities and returns
     * a CSV of their ids:
     *
     *  4, 6, 10
     *
     * This is useful because a "WHERE IN" needs to to be a string, not
     * an array, strangely enough
     *
     * @static
     * @param array $objects
     * @return string
     */
    static private function objectsToIdsString(array $objects)
    {
        $ids = array();
        foreach ($objects as $object) {
            $ids[] = $object->getId();
        }

        return implode(',', $ids);
    }
}
