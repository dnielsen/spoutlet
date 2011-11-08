<?php

namespace Platformd\GiveawayBundle\Entity;

use Platformd\SpoutletBundle\Entity\AbstractEventRepository;

use Doctrine\ORM\NoResultException;

/**
 * Giveaway Repository
 */
class GiveawayRepository extends AbstractEventRepository
{
    /**
     * Create a QueryBuilder instance with base criterias
     *
     * @return Doctrine\ORM\QueryBuilder
     */
    protected function createActiveQueryBuilder()
    {
        return $this
            ->createQueryBuilder('g')
            ->where('g.status != :flag')
            ->setParameter('flag', 'disabled');
    }

    /**
     * Find actives giveaways
     *
     * @return array
     */
    public function findActives()
    {
        
        return $this
            ->createActiveQueryBuilder()
            ->getQuery()
            ->getResult();
    }

    /**
     * Retrieve a Giveaway using a slug
     * 
     * @param string $slug
     * @return Platformd\GiveawayBundle\Entity\Giveway|null
     */
    public function findOneBySlug($slug)
    {
        try {

            return $this
                ->createActiveQueryBuilder()
                ->andWhere('g.slug = :slug')
                ->setParameter('slug', $slug)
                ->getQuery()
                ->getSingleResult();
        } catch(NoResultException $e) {

            return null;
        }
    }
}
