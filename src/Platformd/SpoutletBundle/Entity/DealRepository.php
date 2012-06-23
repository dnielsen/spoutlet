<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;

class DealRepository extends EntityRepository
{
    /**
     * @param string $site
     * @return \Platformd\SpoutletBundle\Entity\Deal[]
     */
    public function findAllForSiteNewestFirst($site)
    {
        return $this->createSiteQueryBuilder($site)
            ->addOrderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @param string $slug
     * @param string $site
     * @return \Platformd\SpoutletBundle\Entity\GamePage
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
     * @return \Platformd\SpoutletBundle\Entity\GamePage
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
     * @param $site
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function createSiteQueryBuilder($site)
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.dealLocales', 'dl')
            ->andWhere('dl.locale = :site')
            ->setParameter('site', $site);
    }
}
