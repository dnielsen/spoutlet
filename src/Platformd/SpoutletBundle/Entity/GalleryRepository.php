<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * GalleryRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class GalleryRepository extends EntityRepository
{
    public function findAllAlphabetically() {
        return $this->createQueryBuilder('g')
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->execute();
    }

    public function findAllGalleriesByCategory($category)
    {
        return $this->createQueryBuilder('ga')
            ->leftJoin('ga.categories', 'cat')
            ->where('cat.name = :category')
            ->orderBy('ga.name', 'ASC')
            ->setParameter('category', $category)
            ->getQuery()
            ->execute();
    }

    public function findAllGalleriesByCategoryForSite($site, $category='image')
    {
        $result = $this->createQueryBuilder('ga')
            ->leftJoin('ga.categories', 'cat')
            ->leftJoin('ga.sites', 's')
            ->where('cat.name = :category')
            ->andWhere('s = :site')
            ->orderBy('ga.name', 'ASC')
            ->setParameter('category', $category)
            ->setParameter('site', $site)
            ->getQuery()
            ->execute();

        return $result;
    }

    public function findAllGalleriesForSite($site)
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.sites', 's')
            ->where('g.deleted = false')
            ->andWhere('s = :site')
            ->setParameter('site', $site)
            ->getQuery()
            ->execute();
    }

    public function findAllGalleries($galleryIds)
    {
        $qb = $this->createQueryBuilder('g');

        return $qb->where($qb->expr()->in('g.id', $galleryIds))
            ->getQuery()
            ->execute();
    }

    public function findOneBySlug($slug)
    {
        return $this->createQueryBuilder('g')
            ->where('g.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
