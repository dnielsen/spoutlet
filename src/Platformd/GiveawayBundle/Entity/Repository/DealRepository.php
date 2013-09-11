<?php

namespace Platformd\GiveawayBundle\Entity\Repository;

use \Platformd\GameBundle\Entity\Game as Game;
use \Platformd\GiveawayBundle\Entity\Deal;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\Query\Expr\Join;

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

    # with eager fetching of pools
    public function findOneByIdAndSiteId($id, $siteId) {
        $qb = $this->createQueryBuilder('d');

        $results = $qb->addSelect('p')
            ->leftJoin('d.pools', 'p')
            ->leftJoin('d.sites', 's')
            ->where('s.id = :siteId')
            ->andWhere('d.id = :dealId')
            ->setParameter('siteId', (int) $siteId)
            ->setParameter('dealId', (int) $id)
            ->getQuery()
            ->getResult();

        if (!$results) {
            return null;
        }

        return $results[0];
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

    public function findOneBySlugWithOpengraphAndMediaForSiteId($slug, $siteId)
    {
        return $this->createSiteIdQueryBuilder($siteId, false)
            ->leftJoin('d.openGraphOverride', 'o')
            ->leftJoin('o.thumbnail', 'ot')
            ->leftJoin('d.banner', 'b')
            ->leftJoin('d.thumbnailLarge', 't')
            ->leftJoin('d.claimCodeButton', 'c')
            ->leftJoin('d.visitWebsiteButton', 'v')
            ->addSelect('o, b, t, c, v, ot')
            ->andWhere('d.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneByIdWithOpengraphAndMediaForSiteId($dealId, $siteId)
    {
        return $this->createSiteIdQueryBuilder($siteId, false)
            ->leftJoin('d.openGraphOverride', 'o')
            ->leftJoin('o.thumbnail', 'ot')
            ->leftJoin('d.banner', 'b')
            ->leftJoin('d.thumbnailLarge', 't')
            ->leftJoin('d.claimCodeButton', 'c')
            ->leftJoin('d.visitWebsiteButton', 'v')
            ->addSelect('o, b, t, c, v, ot')
            ->andWhere('d.id = :dealId')
            ->setParameter('dealId', $dealId)
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

    public function findFeaturedDealsForSiteId($siteId)
    {
        $qb = $this->createSiteIdQueryBuilder($siteId);
        $this->addActiveQueryBuilder($qb);
        $qb->andWhere('d.featured = 1');
        $qb->orderBy('d.featuredAt', 'DESC');

        $qb->leftJoin('d.banner', 'b')
            ->leftJoin('d.thumbnailLarge', 't')
            ->addSelect('b, t');

        $qb->leftJoin('d.pools', 'p')
            ->leftJoin('p.codes', 'c')
            ->andWhere('c.user IS NULL')
            ->andHaving('COUNT(c.id) > 0')
            ->addGroupBy('d.id');

        return $qb
            ->getQuery()
            ->setMaxResults(4)
            ->execute()
        ;
    }

    public function findAllActiveDealsForSiteId($siteId, $ignoreFeatured=false, $featuredToIgnore=null)
    {
        $qb = $this->createSiteIdQueryBuilder($siteId);
        $this->addActiveQueryBuilder($qb);
        $this->addOrderByQuery($qb);

        if ($ignoreFeatured && $featuredToIgnore) {

            foreach ($featuredToIgnore as $featuredDeal) {
                $featuredIds[] = $featuredDeal->getId();
            }

            if (count($featuredIds) > 0) {
                $qb->andWhere($qb->expr()->notIn('d.id', $featuredIds));
            }
        }

        $qb->leftJoin('d.banner', 'b')
            ->leftJoin('d.thumbnailLarge', 't')
            ->addSelect('b, t');

        $qb->leftJoin('d.pools', 'p')
            ->leftJoin('p.codes', 'c')
            ->andWhere('c.user IS NULL')
            ->andHaving('COUNT(c.id) > 0')
            ->addGroupBy('d.id');

        return $qb->getQuery()
            ->execute()
        ;
    }

    public function findExpiredDealsForSiteId($siteId, $maxResults = 4)
    {
        $qb = $this->createSiteIdQueryBuilder($siteId);
        $this->addExpiredQueryBuilder($qb);
        $this->addOrderByQuery($qb);

        $qb->leftJoin('d.banner', 'b')
            ->leftJoin('d.thumbnailLarge', 't')
            ->addSelect('b, t');

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
            $qb->andWhere('s.name = :site');
        } else {
            $qb->andWhere('s = :site');
        }

        $qb->setParameter('site', $site);

        if ($returnOnlyPublished) {
            $this->addPublishedQueryBuilder($qb);
        }

        return $qb;
    }

    private function createSiteIdQueryBuilder($siteId, $returnOnlyPublished = true)
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.sites', 's')
            ->andWhere('s.id = :siteId')
            ->setParameter('siteId', (int) $siteId);

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

        $qb->leftJoin('d.pools', 'p')
            ->leftJoin('p.codes', 'c', Join::WITH, 'p.id = c.pool AND c.user IS NULL')
            ->andWhere('c.user IS NULL')
            ->addGroupBy('d.id');

        $qb->andHaving('(d.endsAt < :now AND d.endsAt IS NOT NULL) OR COUNT(c.id) < 1 OR COUNT(p.id) < 1');
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
