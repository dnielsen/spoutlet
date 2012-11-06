<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * GalleryRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class GalleryMediaRepository extends EntityRepository
{
    public function findAllUnpublishedByUser($user)
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.author = :user')
            ->andWhere('gm.published = :published')
            ->setParameter('user', $user)
            ->setParameter('published', false)
            ->getQuery()
            ->execute();
    }

    public function findAllFeaturedForCategory($category)
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.featured = :featured')
            ->andWhere('gm.category = :category')
            ->setParameter('category', $category)
            ->setParameter('featured', true)
            ->getQuery()
            ->execute();
    }

    public function findMediaForIndexPage($limit=5)
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.published = true')
            ->orderBy('gm.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();
    }

    public function findMediaForGalleryByGalleryId($galleryId)
    {
        return $this->createQueryBuilder('gm')
            ->leftJoin('gm.galleries', 'gmg')
            ->where('gmg.id = :galleryId')
            ->setParameter('galleryId', $galleryId)
            ->getQuery()
            ->execute();
    }
}
