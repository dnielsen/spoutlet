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

    public function findAllUnpublishedByUserForContest($user, $contest)
    {
        return $this->createQueryBuilder('gm')
            ->leftJoin('gm.contestEntry', 'ce')
            ->where('gm.author = :user')
            ->andWhere('gm.published = :published')
            ->andWhere('gm.deleted <> 1')
            ->andWhere('ce.contest = :contest')
            ->setParameter('contest', $contest)
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

    public function findAllPublishedByUserNewestFirstExcept($user, $id, $site)
    {
        return $this->createQueryBuilder('gm')
            ->leftJoin('gm.galleries', 'g')
            ->leftJoin('g.sites', 's')
            ->where('gm.author = :user')
            ->andWhere('gm.published = 1')
            ->andWhere('gm.deleted <> 1')
            ->andWhere('gm.id != :id')
            ->andWhere('s.id = :site')
            ->orderBy('gm.createdAt', 'DESC')
            ->setParameter('user', $user)
            ->setParameter('id', $id)
            ->setParameter('site', $site->getId())
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
            ->leftJoin('gm.contestEntry', 'ce')
            ->leftJoin('ce.contest', 'c')
            ->where('gm.published = true')
            ->andWhere('gm.contestEntry IS NULL OR c.votingEnd < :now')
            ->andWhere('gm.featured = true')
            ->andWhere('gm.deleted = false')
            ->orderBy('gm.createdAt', 'DESC')
            ->setParameter('now', new \DateTime('now'))
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();
    }

    public function findFeaturedMediaForSite($site, $limit=12)
    {
        return $this->createQueryBuilder('gm')
            ->leftJoin('gm.contestEntry', 'ce')
            ->leftJoin('ce.contest', 'c')
            ->leftJoin('gm.galleries', 'g')
            ->leftJoin('g.sites', 's')
            ->where('gm.published = true')
            ->andWhere('gm.contestEntry IS NULL OR c.votingEnd < :now')
            ->andWhere('gm.featured = true')
            ->andWhere('gm.deleted = false')
            ->andWhere('s.id = :site')
            ->orderBy('gm.createdAt', 'DESC')
            ->setParameter('now', new \DateTime('now'))
            ->setParameter('site', $site->getId())
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();
    }

    public function findLatestMedia($limit=12)
    {
        return $this->createQueryBuilder('gm')
            ->leftJoin('gm.contestEntry', 'ce')
            ->leftJoin('ce.contest', 'c')
            ->where('gm.published = true')
            ->andWhere('gm.contestEntry IS NULL OR c.votingEnd < :now')
            ->andWhere('gm.deleted = false')
            ->orderBy('gm.createdAt', 'DESC')
            ->setParameter('now', new \DateTime('now'))
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();
    }

    public function findLatestMediaForSite($site, $limit=12)
    {
        return $this->createQueryBuilder('gm')
            ->leftJoin('gm.galleries', 'gmg')
            ->leftJoin('gmg.sites', 's')
            ->leftJoin('gm.contestEntry', 'ce')
            ->leftJoin('ce.contest', 'c')
            ->where('gm.published = true')
            ->andWhere('gm.contestEntry IS NULL OR c.votingEnd < :now')
            ->andWhere('gm.deleted = false')
            ->andWhere('s.id = :siteId')
            ->setParameter('siteId', $site->getId())
            ->setParameter('now', new \DateTime('now'))
            ->orderBy('gm.createdAt', 'DESC')
            ->distinct('gm.id')
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();
    }

    public function findPopularMedia($limit=12)
    {
        $results = $this->getEntityManager()->createQuery('
            SELECT DISTINCT
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

    public function findPopularMediaForSite($site, $limit=12)
    {

        $results = $this->getEntityManager()->createQuery('
            SELECT DISTINCT
                gm, COUNT(gmv.id) as vote_count
            FROM
                SpoutletBundle:GalleryMedia gm
            LEFT JOIN gm.votes gmv
            LEFT JOIN gm.galleries gmg
            LEFT JOIN gmg.sites s
            LEFT JOIN gm.contestEntry ce
            LEFT JOIN ce.contest c
            WHERE gm.deleted = 0
            AND (gm.contestEntry IS NULL OR c.votingEnd < :now)
            AND gm.published = 1
            AND gmv.voteType = :up
            AND s.id = :siteId
            GROUP BY gm.id
            ORDER BY vote_count DESC'
        )->setMaxResults($limit)
        ->setParameter('up', 'up')
        ->setParameter('now', new \DateTime('now'))
        ->setParameter('siteId', $site->getId())
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
            }

            $ids = count($contest->getWinners()) > 0 ? $contest->getWinners() : array(0);

            return $qb->where($qb->expr()->in('gm.id', $ids))
                ->andWhere('gm.deleted = false')
                ->andWhere('gm.published = true')
                ->getQuery()
                ->execute();

    }

    public function findAllDeletedMediaForUser($user)
    {
         return $this->createQueryBuilder('gm')
            ->where('gm.author = :user')
            ->andWhere('gm.deleted = 1')
            ->orderBy('gm.createdAt', 'DESC')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function findAllMediaByUserAndGallery($user, $gallery)
    {
        return $this->createQueryBuilder('gm')
            ->leftJoin('gm.galleries', 'gmg')
            ->where('gm.author = :user')
            ->andWhere('gmg.id = :galleryId')
            ->andWhere('gm.published = 1')
            ->andWhere('gm.deleted <> 1')
            ->orderBy('gm.createdAt', 'DESC')
            ->setParameter('user', $user)
            ->setParameter('galleryId', $gallery->getId())
            ->getQuery()
            ->execute();
    }

    public function findImagesForGroup($group)
    {
        return $this->createQueryBuilder('gm')
            ->leftJoin('gm.groups', 'g')
            ->where('g IN (:group)')
            ->andWhere('gm.published = 1')
            ->andWhere('gm.deleted <> 1')
            ->orderBy('gm.createdAt', 'DESC')
            ->setParameter('group', $group)
            ->getQuery()
            ->execute();
    }

    public function findImagesForMetrics($title, $deleted, $status, $sites, $startDate="", $endDate="", $execute = true)
    {
        $siteIds = array();
        foreach ($sites as $site) {
            $siteIds[] = $site->getId();
        }

        $qb = $this->createQueryBuilder('gm')
            ->leftJoin('gm.galleries', 'g')
            ->leftJoin('g.sites', 's')
            ->leftJoin('gm.votes', 'v');

        if (count($sites) > 0) {

            $qb->andWhere('(s.id IN (:siteList))');
            $qb->setParameter('siteList', $siteIds);

        }

        if ($title) {
            $qb->andWhere('gm.title like :title');
            $qb->setParameter('title', '%'.$title.'%');
        }

        if ($deleted != "") {
            $qb->andWhere('gm.deleted = :deleted');
            $qb->setParameter('deleted', $deleted);
        }

        if ($status != "") {
            $qb->andWhere('gm.published = :status');
            $qb->setParameter('status', $status);
        }

        if ($startDate != "") {

            $startDate->setTime(0, 0, 0);
            $qb->andWhere('gm.createdAt >= :startDate');
            $qb->setParameter('startDate', $startDate);
        }

        if ($endDate != "") {

            $endDate->setTime(23, 59, 59);
            $qb->andWhere('gm.createdAt <= :endDate');
            $qb->setParameter('endDate', $endDate);
        }

        if ($execute) {
            return $qb->getQuery()->execute();
        }

        return $qb->getQuery();
    }
}
