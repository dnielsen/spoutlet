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
            ->andWhere('gm.deleted <> 1')
            ->andWhere('gm.published = :published')
            ->andWhere('gm.contestEntry IS NULL')
            ->setParameter('user', $user)
            ->setParameter('published', false)
            ->getQuery()
            ->execute();
    }

    public function findAllUnpublishedByUserForContest($user)
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.author = :user')
            ->andWhere('gm.published = :published')
            ->andWhere('gm.deleted <> 1')
            ->andWhere('gm.contestEntry IS NOT NULL')
            ->setParameter('user', $user)
            ->setParameter('published', false)
            ->getQuery()
            ->execute();
    }

    public function findAllPublishedByUserNewestFirst($user)
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.author = :user')
            ->andWhere('gm.published = 1')
            ->andWhere('gm.deleted <> 1')
            ->orderBy('gm.createdAt', 'DESC')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function findAllPublishedByUserNewestFirstExcept($user, $id)
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.author = :user')
            ->andWhere('gm.published = 1')
            ->andWhere('gm.deleted <> 1')
            ->andWhere('gm.id != :id')
            ->orderBy('gm.createdAt', 'DESC')
            ->setParameter('user', $user)
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }

    public function findAllFeaturedForCategory($category)
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.featured = true')
            ->andWhere('gm.deleted <> 1')
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
            AND gmv.voteType = :up
            GROUP BY gm.id
            ORDER BY vote_count DESC'
        )->setMaxResults($limit)
        ->setParameter('up', 'up')
        ->execute();

        return $results;
    }

    public function findMediaForGalleryByGalleryId($galleryId, $limit=12)
    {
        return $this->createQueryBuilder('gm')
            ->leftJoin('gm.galleries', 'gmg')
            ->leftJoin('gm.contestEntry', 'ce')
            ->leftJoin('ce.contest', 'c')
            ->where('gmg.id = :galleryId')
            ->andWhere('gm.published = true')
            ->andWhere('gm.deleted = false')
            ->andWhere('gm.contestEntry IS NULL OR c.votingEnd < :now')
            ->orderBy('gm.createdAt', 'DESC')
            ->setParameter('galleryId', $galleryId)
            ->setParameter('now', new \DateTime('now'))
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();
    }

    public function findTopMediaForGallery($gallery, $limit=12)
    {
        return $this->createQueryBuilder('gm')
            ->select('gm', 'COUNT(v.id) AS vote_count')
            ->leftJoin('gm.votes', 'v')
            ->leftJoin('gm.galleries', 'gmg')
            ->leftJoin('gm.contestEntry', 'ce')
            ->leftJoin('ce.contest', 'c')
            ->andWhere('gmg.id = :galleryId')
            ->andWhere('gm.published = true')
            ->andWhere('gm.deleted <> 1')
            ->andWhere('gm.contestEntry IS NULL OR c.votingEnd < :now')
            ->groupBy('gm.id')
            ->orderBy('vote_count', 'DESC')
            ->setParameter('galleryId', $gallery->getId())
            ->setParameter('now', new \DateTime('now'))
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();
    }

    public function findLatestMediaForGallery($gallery, $limit=12)
    {
        return $this->createQueryBuilder('gm')
            ->leftJoin('gm.galleries', 'gmg')
            ->leftJoin('gm.contestEntry', 'ce')
            ->leftJoin('ce.contest', 'c')
            ->where('gmg.id = :galleryId')
            ->andWhere('gm.published = true')
            ->andWhere('gm.deleted = false')
            ->andWhere('gm.contestEntry IS NULL OR c.votingEnd < :now')
            ->orderBy('gm.createdAt', 'DESC')
            ->setParameter('galleryId', $gallery->getId())
            ->setParameter('now', new \DateTime('now'))
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();
    }

    public function findMediaForIndexPage($limit=5)
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.published = true')
            ->andWhere('gm.deleted <> 1')
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
            ->andWhere('gm.deleted <> 1')
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

    public function findMediaForContestWinners($contest)
    {
        if ($contest->getWinners()) {
            $qb =  $this->createQueryBuilder('gm');

        $ids = count($contest->getWinners()) > 0 ? $contest->getWinners() : array(0);

        return $qb->where($qb->expr()->in('gm.id', $ids))
            ->andWhere('gm.deleted = false')
            ->andWhere('gm.published = true')
            ->getQuery()
            ->execute();
    }
}
