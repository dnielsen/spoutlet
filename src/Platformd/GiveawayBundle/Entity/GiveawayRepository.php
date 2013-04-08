<?php

namespace Platformd\GiveawayBundle\Entity;

use Platformd\SpoutletBundle\Entity\AbstractEventRepository;

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;

/**
 * Giveaway Repository
 */
class GiveawayRepository extends AbstractEventRepository
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

    /**
     * Returns ALL giveaways, from newest to oldest
     *
     * @return \Platformd\GiveawayBundle\Entity\Giveway[]
     */
    public function findAllOrderedByNewest()
    {
        return $this->createQueryBuilder('g')
            ->orderBy('g.created', 'DESC')
            ->getQuery()
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

        $qb->leftJoin('g.sites', 's');
        $qb->andWhere(is_string($site) ? 's.name = :site' : 's = :site');
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
}
