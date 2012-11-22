<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;


class VoteRepository extends EntityRepository
{
    public function findVotes($media)
    {
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.galleryMedia = :media')
            ->setParameter('media', $media);

        return $qb->getQuery()->execute();
    }

    public function findUpVotes($media)
    {
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.galleryMedia = :media')
            ->andWhere('v.voteType = :voteType')
            ->select('COUNT(v)')
            ->setParameter('media', $media)
            ->setParameter('voteType', 'up');

        $result = $qb->getQuery()->execute();

        return $result[0][1];
    }

    public function canVoteOnMedia($media, $user)
    {
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.galleryMedia = :media')
            ->andWhere('v.user = :user')
            ->setParameter('media', $media)
            ->setParameter('user', $user);

        $existingVote = $qb->getQuery()->execute();

        return !$existingVote;
    }

    public function getVotesForContests()
    {
        return $this->createQueryBuilder('v')
            ->select('c.id', 'COUNT(v.id) AS vote_count')
            ->leftJoin('v.galleryMedia', 'gm')
            ->leftJoin('gm.contestEntry', 'ce')
            ->leftJoin('ce.contest', 'c')
            ->andWhere('gm.contestEntry IS NOT NULL')
            ->andWhere('v.voteType = :up')
            ->setParameter('up', 'up')
            ->groupBy('c.id')
            ->getQuery()
            ->execute();
    }

    public function getVotesForContest($contest)
    {
        return $this->createQueryBuilder('v')
            ->select('gm.id', 'COUNT(v.id) AS vote_count')
            ->leftJoin('v.galleryMedia', 'gm')
            ->leftJoin('gm.contestEntry', 'ce')
            ->leftJoin('ce.contest', 'c')
            ->andWhere('c = :contest')
            ->andWhere('v.voteType = :up')
            ->setParameter('contest', $contest)
            ->setParameter('up', 'up')
            ->groupBy('gm.id')
            ->getQuery()
            ->execute();
    }
}
