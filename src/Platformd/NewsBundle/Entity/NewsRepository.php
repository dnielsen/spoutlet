<?php

namespace Platformd\NewsBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Platformd\SpoutletBundle\Entity\Game;

/**
 * NewsRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class NewsRepository extends EntityRepository
{
    /**
     * Used in the admin
     *
     * @return \Doctrine\ORM\Query
     */
    public function getFindNewsQuery()
    {
        return $this->createQueryBuilder('n')
            ->orderBy('n.postedAt', 'DESC')
            ->getQuery();
    }

    /**
     * @return array
     */
    public function findAllForSite($site)
    {
        return $this->createBaseQueryBuilder($site)
            ->orderBy('n.postedAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return \Platformd\NewsBundle\Entity\News;
     */
    public function findOneFeaturedForSite($site)
    {
        // todo - this will need to do something smarter - see #68
        return $this->createBaseQueryBuilder($site)
            ->orderBy('n.postedAt', 'DESC')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;
    }

    /**
     * @param $num
     * @return \Platformd\NewsBundle\Entity\News[]
     */
    public function findMostRecentForSite($site, $num)
    {
        return $this->createBaseQueryBuilder($site)
            ->orderBy('n.postedAt', 'DESC')
            ->getQuery()
            ->setMaxResults($num)
            ->execute()
        ;
    }

    /**
     * @param Game $game
     * @return \Platformd\NewsBundle\Entity\News[]
     */
    public function findActivesForGame(Game $game, $site)
    {
        return $this->createBaseQueryBuilder($site)
            ->andWhere('n.game = :game')
            ->setParameter('game', $game)
            ->orderBy('n.postedAt', 'DESC')
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * Creates a base query builder that's locale-aware and only returns
     * published entries
     * @param \Doctrine\ORM\QueryBuilder|null $qb
     * @return \Doctrine\ORM\QueryBuilder|null
     */
    protected function createBaseQueryBuilder($site, QueryBuilder $qb = null)
    {
        if ($qb === null) {
            $qb = $this->createQueryBuilder('n');
        }

        $qb->leftJoin('n.sites', 's')
            ->andWhere('n.published = 1');

        if (is_string($site)) {
            $qb->andWhere('s.name = :site')
                ->setParameter('site', $site);

            return $qb;
        }

        $qb->andWhere('s = :site')
            ->setParameter('site', $site);

        return $qb;
    }
}
