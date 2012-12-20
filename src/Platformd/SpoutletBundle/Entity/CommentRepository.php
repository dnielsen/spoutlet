<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;

class CommentRepository extends EntityRepository
{
    public function findCommentsForThreadSortedByDate($thread, $limit=25)
    {
        $result = $this->createQueryBuilder('c')
            ->leftJoin('c.thread', 't')
            ->andWhere('t.id = :thread')
            ->andWhere('c.parent IS NULL')
            ->andWhere('c.deleted <> true')
            ->setParameter('thread', $thread)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();

        return $result;
    }

    public function findCommentsForThreadSortedByVotes($thread, $limit=25)
    {
        $result = $this->createQueryBuilder('c')
            ->select('c, (SELECT COUNT(v1.id) FROM SpoutletBundle:CommentVote v1 WHERE v1.voteType=:up AND v1.comment=c) AS upvotes')
            ->leftJoin('c.thread', 't')
            ->leftJoin('c.votes', 'v')
            ->andWhere('t.id = :thread')
            ->andWhere('c.parent IS NULL')
            ->andWhere('c.deleted <> true')
            ->setParameter('thread', $thread)
            ->setParameter('up', 'up')
            ->orderBy('upvotes', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();

        return $result;
    }
}
