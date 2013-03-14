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

    public function findUpVotesCount($media, $contest=null)
    {
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.galleryMedia = :media')
            ->andWhere('v.voteType = :voteType')
            ->select('COUNT(v)')
            ->setParameter('media', $media)
            ->setParameter('voteType', 'up');

        if ($contest) {
            $qb->andWhere('v.votedAt < :contestEnd')
                ->setParameter('contestEnd', $contest->getVotingEndUtc());
        }

        $result = $qb->getQuery()->execute();

        return $result[0][1];
    }

    public function findVoteCount($media)
    {
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.galleryMedia = :media')
            ->select('COUNT(v)')
            ->setParameter('media', $media);

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
            ->andWhere('v.votedAt < c.votingEndUtc')
            ->setParameter('up', 'up')
            ->groupBy('c.id')
            ->getQuery()
            ->execute();
    }

    public function getVotesForContest($contest)
    {
        $upvotes = $this->createQueryBuilder('v')
            ->select('gm.id', 'COUNT(v.id) AS vote_count')
            ->leftJoin('v.galleryMedia', 'gm')
            ->leftJoin('gm.contestEntry', 'ce')
            ->leftJoin('ce.contest', 'c')
            ->andWhere('c = :contest')
            ->andWhere('v.voteType = :up')
            ->andWhere('v.votedAt < :voteEnd')
            ->setParameter('contest', $contest)
            ->setParameter('voteEnd', $contest->getVotingEndUtc())
            ->setParameter('up', 'up')
            ->groupBy('gm.id')
            ->getQuery()
            ->execute();

        return $upvotes;
    }

    public function findUpVotesInArray($idArray)
    {
        $qb = $this->createQueryBuilder('v')
            ->leftJoin('v.galleryMedia', 'gm')
            ->andWhere('v.voteType = :voteType')
            ->andWhere('gm.id IN (:ids)')
            ->select('gm.id', 'COUNT(v) likeCount')
            ->setParameter('voteType', 'up')
            ->setParameter('ids', $idArray)
            ->groupBy('gm.id');

        $result = $qb->getQuery()->execute();

        $return = array();

        foreach ($result as $mediaVotes) {
            $return[$mediaVotes['id']] = $mediaVotes['likeCount'];
        }

        return $return;
    }

    public function findUpVotes($media, $contest=null)
    {
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.galleryMedia = :media')
            ->andWhere('v.voteType = :voteType')
            ->select('v')
            ->setParameter('media', $media)
            ->setParameter('voteType', 'up');

        if ($contest) {
            $qb->andWhere('v.votedAt < :contestEnd')
                ->setParameter('contestEnd', $contest->getVotingEndUtc());
        }

        $result = $qb->getQuery()->execute();

        return $result;
    }
}
