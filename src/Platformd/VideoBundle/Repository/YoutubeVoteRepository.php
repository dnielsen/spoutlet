<?php

namespace Platformd\VideoBundle\Repository;

use Doctrine\ORM\EntityRepository;

class YoutubeVoteRepository extends EntityRepository
{
    public function canVoteOnVideo($video, $user)
    {
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.video = :video')
            ->andWhere('v.user = :user')
            ->setParameter('video', $video)
            ->setParameter('user', $user);

        $existingVote = $qb->getQuery()->execute();

        return !$existingVote;
    }
}
