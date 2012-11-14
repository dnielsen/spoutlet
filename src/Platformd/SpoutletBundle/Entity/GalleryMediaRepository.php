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

    public function findAllPublishedByUserNewestFirst($user)
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.author = :user')
            ->andWhere('gm.published = :published')
            ->orderBy('gm.createdAt', 'DESC')
            ->setParameter('user', $user)
            ->setParameter('published', true)
            ->getQuery()
            ->execute();
    }

    public function findAllPublishedByUserNewestFirstExcept($user, $id)
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.author = :user')
            ->andWhere('gm.published = :published')
            ->andWhere('gm.id != :id')
            ->orderBy('gm.createdAt', 'DESC')
            ->setParameter('user', $user)
            ->setParameter('published', true)
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }

    public function findAllFeaturedForCategory($category)
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.featured = true')
            ->andWhere('gm.category = :category')
            ->setParameter('category', $category)
            ->getQuery()
            ->execute();
    }

    public function findMediaForNivoSlider($limit=5)
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.published = true')
            ->andWhere('gm.deleted = false')
            ->orderBy('gm.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();
    }

    public function findFeaturedMedia($limit=12)
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.published = true')
            ->andWhere('gm.featured = true')
            ->andWhere('gm.deleted = false')
            ->orderBy('gm.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();
    }

    public function findLatestMedia($limit=12)
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.published = true')
            ->andWhere('gm.deleted = false')
            ->orderBy('gm.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();
    }

    public function findPopularMedia($limit=12)
    {
        $results = $this->getEntityManager()->createQuery('
            SELECT
                gm, COUNT(gmv.id) as vote_count
            FROM
                SpoutletBundle:GalleryMedia gm
            LEFT JOIN gm.votes gmv
            WHERE gm.deleted = 0
            AND gm.published = 1
            GROUP BY gm.id
            ORDER BY vote_count DESC'
        )->setMaxResults($limit)->execute();

        return $results;
    }

    public function findMediaForGalleryByGalleryId($galleryId, $limit=12)
    {
        return $this->createQueryBuilder('gm')
            ->leftJoin('gm.galleries', 'gmg')
            ->where('gmg.id = :galleryId')
            ->andWhere('gm.published = true')
            ->andWhere('gm.deleted = false')
            ->orderBy('gm.createdAt', 'DESC')
            ->setParameter('galleryId', $galleryId)
            ->setMaxResults($limit)
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

    public function findMostRecentPublishedByUser($user)
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.published = true')
            ->andWhere('gm.deleted = false')
            ->andWhere('gm.author = :user')
            ->orderBy('gm.publishedAt', 'DESC')
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findMediaForContest($contest)
    {
        return $this->createQueryBuilder('gm')
            ->leftJoin('gm.contestEntry', 'ce')
            ->where('ce.contest = :contest')
            ->andWhere('gm.deleted = false')
            ->andWhere('gm.published = true')
            ->setParameter('contest', $contest)
            ->getQuery()
            ->execute();
    }
}
