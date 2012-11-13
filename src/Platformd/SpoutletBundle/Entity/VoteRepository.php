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
}
