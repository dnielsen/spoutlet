<?php

namespace Platformd\SpoutletBundle\Entity;

use \Platformd\SpoutletBundle\Entity\Game as Game;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use DateTime;
use DateTimeZone;

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
     * @return \Platformd\SpoutletBundle\Entity\Deal[]
     */
    public function findAllOrderedByNewest()
    {
        return $this->createQueryBuilder('d')
            ->addOrderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * Get all published deals for this site and game
     *
     * @param string $site
     * @return \Platformd\SpoutletBundle\Entity\Deal[]
     */
    public function findAllPublishedForSiteNewestFirstForGame($site, Game $game)
    {

        $qb = $this->createSiteQueryBuilder($site, true);
        $qb = $this->addActiveQueryBuilder($qb);
        $qb = $this->addGameQueryBuilder($qb, $game);

        return $qb->addOrderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->execute();
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
        $qb = $this->createSiteQueryBuilder($site);
        $this->addActiveQueryBuilder($qb);
        $this->addOrderByQuery($qb);

        return $qb
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
        $this->addOrderByQuery($qb);

        if (!empty($featuredDeals)) {
            $qb->andWhere($qb->expr()->notIn('d.id', self::objectsToIdsArray($featuredDeals)));
        }

        return $qb->getQuery()
            ->execute()
        ;
    }

        /**
     * Finds all active deals, except for those "featured", which are passed in
     *
     * @param string $site
     * @return \Platformd\SpoutletBundle\Entity\Deal[]
     */
    public function findAllActiveDealsForSite($site)
    {
        $qb = $this->createSiteQueryBuilder($site);
        $this->addActiveQueryBuilder($qb);
        $this->addOrderByQuery($qb);

        return $qb->getQuery()
            ->execute()
        ;
    }

    /**
     * Finds all expired deals
     *
     * @param string $site
     * @return \Platformd\SpoutletBundle\Entity\Deal[]
     */
    public function findExpiredDealsForSite($site)
    {
        $qb = $this->createSiteQueryBuilder($site);
        $this->addExpiredQueryBuilder($qb);
        $this->addOrderByQuery($qb);

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
     * Adds game to a query
     *
     * @param null|QueryBuilder $qb
     * @return \Doctrine\ORM\QueryBuilder|null|QueryBuilder
     */
    private function addGameQueryBuilder(QueryBuilder $qb = null, Game $game)
    {
        if ($qb === null) {
            $qb = $this->createQueryBuilder('d');
        }

        $qb->andWhere('d.game = :game')->setParameter('game', $game);

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
        $qb->andWhere('d.endsAt > :now OR d.endsAt IS NULL');
        $qb->setParameter('now', new DateTime('now', new DateTimeZone('UTC')));

        return $qb;
    }

    /**
     * Adds a query builder to return only "expired" deals:
     *      * deals that have not already started
     *      * deals that have expired
     *
     * This is the opposite of addActiveQueryBuilder
     *
     * @param QueryBuilder|null $qb
     * @return \Doctrine\ORM\QueryBuilder|null
     */
    private function addExpiredQueryBuilder(QueryBuilder $qb = null)
    {
        if ($qb === null) {
            $qb = $this->createQueryBuilder('d');
        }

        $qb->andWhere('d.startsAt < :now AND d.startsAt IS NOT NULL');
        $qb->andWhere('d.endsAt < :now AND d.endsAt IS NOT NULL');
        $qb->setParameter('now', new DateTime('now', new DateTimeZone('UTC')));

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function addOrderByQuery(QueryBuilder $qb)
    {
        $qb->addOrderBy('d.createdAt', 'DESC');

        return $qb;
    }

    /**
     * Utility function that takes an array of entities and returns
     * an array of their ids
     *
     * @static
     * @param array $objects
     * @return array
     */
    static private function objectsToIdsArray(array $objects)
    {
        $ids = array();
        foreach ($objects as $object) {
            $ids[] = $object->getId();
        }

        return $ids;
    }
}
