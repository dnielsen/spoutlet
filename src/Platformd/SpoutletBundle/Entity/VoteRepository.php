<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;


class VoteRepository extends EntityRepository
{
    public function findVotes($media, $contest = null)
    {
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.galleryMedia = :media')
            ->setParameter('media', $media);

        if ($contest) {
            $qb->andWhere('v.contest = :contest')
                ->setParameter('contest', $contest);
        }

        return $qb->getQuery()->execute();
    }

    public function findUpVotes($media, $contest = null)
    {
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.galleryMedia = :media')
            ->andWhere('v.voteType = :voteType')
            ->setParameter('media', $media)
            ->setParameter('voteType', 'up');

        if ($contest) {
            $qb->andWhere('v.contest = :contest')
                ->setParameter('contest', $contest);
        } else {
            $qb->andWhere('v.contest IS NULL');
        }

        return $qb->getQuery()->execute();
    }

    public function canVoteOnMedia($media, $contest, $user)
    {
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.galleryMedia = :media')
            ->andWhere('v.user = :user')
            ->setParameter('media', $media)
            ->setParameter('user', $user);

        if ($contest === null) {
            $qb->andWhere('v.contest IS NULL');
        } else {
            $qb->andWhere('v.contest = :contest')
            ->setParameter('contest', $contest);
        }

        $existingVote = $qb->getQuery()->execute();

        return !$existingVote;
    }
}
