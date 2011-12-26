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
     * @param $locale
     * @return array
     */
    public function findActives($locale)
    {
        
        return $this
            ->createActiveQueryBuilder($locale)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $locale
     * @return array
     */
    public function findAllForLocale($locale)
    {
        return $this->createBaseQueryBuilder($locale)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Retrieve a Giveaway using a slug
     * 
     * @param string $slug
     * @param string $locale
     * @return Platformd\GiveawayBundle\Entity\Giveway|null
     */
    public function findOneBySlug($slug, $locale)
    {
        try {

            return $this
                ->createBaseQueryBuilder($locale)
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
     * @return mixed
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
     * Creates a base query builder that's locale-aware
     *
     * @param $locale
     * @param \Doctrine\ORM\QueryBuilder|null $qb
     * @return \Doctrine\ORM\QueryBuilder|null
     */
    protected function createBaseQueryBuilder($locale, QueryBuilder $qb = null)
    {
        if ($qb === null) {
            $qb = $this->createQueryBuilder('g');
        }

        $qb->andWhere('g.locale = :locale')
            ->setParameter('locale', $locale)
        ;

        return $qb;
    }

    /**
     * Create a QueryBuilder instance with base criterias
     *
     * @param string $locale The locale we're working in
     * @return Doctrine\ORM\QueryBuilder
     */
    protected function createActiveQueryBuilder($locale)
    {
        $qb = $this->createBaseQueryBuilder($locale);

        $qb->andWhere('g.status != :flag')
            ->setParameter('flag', 'disabled')
        ;

        return $qb;
    }
}
