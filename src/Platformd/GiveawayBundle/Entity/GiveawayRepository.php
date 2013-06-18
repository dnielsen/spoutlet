<?php

namespace Platformd\GiveawayBundle\Entity;

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityRepository;

use DateTime;
use Platformd\GiveawayBundle\Entity\Giveaway;
use Platformd\GameBundle\Entity\Game;

/**
 * Giveaway Repository
 */
class GiveawayRepository extends EntityRepository
{
    /**
     * Find actives giveaways
     *
     * @param $site
     * @return array
     */
    public function findActives($site)
    {
        return $this
            ->createActiveQueryBuilder($site)
            ->andWhere('g.featured != 1')
            ->orderBy('g.created', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $site
     * @return array
     */
    public function findAllForSite($site)
    {
        return $this->createBaseQueryBuilder($site)
            ->orderBy('g.created', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAllActiveForSiteWithLimit($locale, $limit = null) {

         return $this->createActiveQueryBuilder($locale)
            ->orderBy('g.created', 'DESC')
            ->getQuery()
            ->setMaxResults($limit)
            ->getResult()
        ;
    }

    /**
     * Retrieve a Giveaway using a slug
     *
     * @param string $slug
     * @param string $site
     * @return \Platformd\GiveawayBundle\Entity\Giveway|null
     */
    public function findOneBySlug($slug, $site)
    {
        try {

            return $this
                ->createBaseQueryBuilder($site)
                ->andWhere('g.slug = :slug')
                ->setParameter('slug', $slug)
                ->getQuery()
                ->getSingleResult();
        } catch(NoResultException $e) {

            return null;
        }
    }
    # with eager fetching of pools
    public function findOneBySlugAndSiteId($slug, $siteId) {
        $qb = $this->createQueryBuilder('g');

        $results = $qb->addSelect('p')
            ->leftJoin('g.pools', 'p')
            ->leftJoin('g.sites', 's')
            ->where('s.id = :siteId')
            ->andWhere('g.slug = :slug')
            ->setParameter('siteId', (int) $siteId)
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getResult();

        if (!$results) {
            return null;
        }

        return $results[0];
    }

    # with eager fetching of pools
    public function findOneByIdAndSiteId($id, $siteId) {
        $qb = $this->createQueryBuilder('g');

        $results = $qb->addSelect('p')
            ->leftJoin('g.pools', 'p')
            ->leftJoin('g.sites', 's')
            ->where('s.id = :siteId')
            ->andWhere('g.id = :giveawayId')
            ->setParameter('siteId', (int) $siteId)
            ->setParameter('giveawayId', (int) $id)
            ->getQuery()
            ->getResult();

        if (!$results) {
            return null;
        }

        return $results[0];
    }
    /**
     * Returns ALL giveaways, from newest to oldest
     *
     * @return \Platformd\GiveawayBundle\Entity\Giveway[]
     */
    public function findAllOrderedByNewest($site=null)
    {
        $qb = $this->createQueryBuilder('g')
            ->orderBy('g.created', 'DESC');

        if ($site) {
            $qb->leftJoin('g.sites', 's')
                ->andWhere('s = :site')
                ->setParameter('site', $site);
        }

        return $qb->getQuery()
            ->execute()
        ;
    }

    /**
     * Returns top 4 (by default) featured giveaways
     * @return \Platformd\GiveawayBundle\Entity\Giveaway[]
     */
    public function findActiveFeaturedForSite($site, $limit=4)
    {
        return $this->createBaseQueryBuilder($site)
            ->andWhere('g.featured = 1')
            ->andWhere('g.status != :flag')
            ->orderBy('g.featuredAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('flag', 'disabled')
            ->getQuery()
            ->execute();
    }

    /**
     * Creates a base query builder that's site-aware
     *
     * @param $site
     * @param \Doctrine\ORM\QueryBuilder|null $qb
     * @return \Doctrine\ORM\QueryBuilder|null
     */
    protected function createBaseQueryBuilder($site, QueryBuilder $qb = null)
    {
        if ($qb === null) {
            $qb = $this->createQueryBuilder('g');
        }

        if (is_numeric($site)) {
            $qb->andWhere('s.id = :site');
        } elseif (is_string($site)) {
            $qb->andWhere('s.name = :site');
        } else {
            $qb->andWhere('s = :site');
        }

        $qb->addSelect('gt'); // eager fetch translations
        $qb->leftJoin('g.translations', 'gt');

        $qb->leftJoin('g.sites', 's');

        $qb->andWhere('g.published = true');
        $qb->setParameter('site', $site);

        return $qb;
    }

    /**
     * Create a QueryBuilder instance with base criterias
     *
     * @param string $site The site we're working in
     * @return Doctrine\ORM\QueryBuilder
     */
    protected function createActiveQueryBuilder($site)
    {
        $qb = $this->createBaseQueryBuilder($site);

        $qb->andWhere('g.status != :flag')
            ->setParameter('flag', 'disabled')
        ;

        return $qb;
    }

    /**
     * @param Game $game
     * @param $siteKey
     * @return \Platformd\GiveawayBundle\Entity\Giveaway[]
     */
    public function findActivesForGame(Game $game, $site)
    {
        $qb = $this->createActiveQueryBuilder($site);
        $query = $this->addCurrentQuery($qb)
            ->orderBy('g.created', 'DESC')
            ->andWhere('g.game = :game')
            ->setParameter('game', $game)
            ->getQuery()
        ;

        return $query->getResult();
    }

    private function addCurrentQuery(QueryBuilder $qb)
    {
        $qb
            ->andWhere('g.ends_at > :cut_off OR g.ends_at IS NULL')
            ->setParameter('cut_off', new \DateTime())
        ;

        return $qb;
    }

    public function getCurrentGiveaways($site, $limit = null)
    {
        $qb = $this->createActiveQueryBuilder($site);
        $query = $this->addCurrentQuery($qb)
            ->orderBy('g.starts_at', 'DESC')
            ->getQuery()
        ;

        $items = $this->addQueryLimit($query, $limit)->getResult();

        return $items;
    }

    public function getPastGiveaways($site, $limit = null)
    {
        $query = $this->createBaseQueryBuilder($site)
            ->andWhere('g.ends_at < :cut_off')
            ->setParameter('cut_off', new \DateTime())
            ->orderBy('g.ends_at', 'DESC')
            ->getQuery();

        return $this->addQueryLimit($query, $limit)->getResult();
    }

    public function findAllWithoutLocaleOrderedByNewest()
    {
        return $this->createQueryBuilder('g')
            ->orderBy('g.created', 'DESC')
            ->getQuery()
            ->execute()
        ;
    }

    private function addQueryLimit(Query $query, $limit)
    {
        if  (null === $limit) {
            return $query;
        }

        return $query->setMaxResults($limit);
    }
}
