<?php

namespace Platformd\GiveawayBundle\Entity\Repository;

use \Platformd\GameBundle\Entity\Game as Game;
use \Platformd\GiveawayBundle\Entity\Deal;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use DateTime;
use DateTimeZone;

class DealRepository extends EntityRepository
{
    /**
     * Includes unpublished Deals
     */
    public function findAllForSiteNewestFirst($site)
    {
        return $this->createSiteQueryBuilder($site, false)
            ->addOrderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->execute()
        ;
    }

    public function findAllOrderedByNewest()
    {
        return $this->createQueryBuilder('d')
            ->addOrderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->execute()
        ;
    }

    public function findAllPublishedForSiteNewestFirstForGame($site, Game $game)
    {
        $qb = $this->createSiteQueryBuilder($site, true);
        $qb = $this->addActiveQueryBuilder($qb);
        $qb = $this->addGameQueryBuilder($qb, $game);

        return $qb->addOrderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->execute();
    }

    public function findOneBySlugForSite($slug, $site)
    {
        return $this->createSiteQueryBuilder($site, false)
            ->andWhere('d.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneByNameForSite($name, $site)
    {
        return $this->createSiteQueryBuilder($site)
            ->andWhere('d.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

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

    public function findAllActiveDealsForSite($site)
    {
        $qb = $this->createSiteQueryBuilder($site);
        $this->addActiveQueryBuilder($qb);
        $this->addOrderByQuery($qb);

        return $qb->getQuery()
            ->execute()
        ;
    }

    public function findExpiredDealsForSite($site, $maxResults = 4)
    {
        $qb = $this->createSiteQueryBuilder($site);
        $this->addExpiredQueryBuilder($qb);
        $this->addOrderByQuery($qb);

        return $qb->getQuery()
            ->setMaxResults($maxResults)
            ->execute()
        ;
    }

    private function createSiteQueryBuilder($site, $returnOnlyPublished = true)
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.sites', 's');

        if (is_string($site)) {
            $qb->andWhere('s.name = :site')
                ->setParameter('site', $site);
        } else {
            $qb->andWhere('s = :site')
            ->setParameter('site', $site);
        }

        if ($returnOnlyPublished) {
            $this->addPublishedQueryBuilder($qb);
        }

        return $qb;
    }

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
     */
    private function addExpiredQueryBuilder(QueryBuilder $qb = null)
    {
        if ($qb === null) {
            $qb = $this->createQueryBuilder('d');
        }

        $qb->andWhere('d.endsAt < :now AND d.endsAt IS NOT NULL');
        $qb->setParameter('now', new DateTime('now', new DateTimeZone('UTC')));

        return $qb;
    }

    private function addOrderByQuery(QueryBuilder $qb)
    {
        $qb->addOrderBy('d.createdAt', 'DESC');

        return $qb;
    }

    /**
     * Utility function that takes an array of entities and returns
     * an array of their ids
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
