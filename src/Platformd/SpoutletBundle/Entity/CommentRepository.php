<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;

class CommentRepository extends EntityRepository
{
    public function findCommentsForThreadSortedByDate($thread, $limit=25, $order='DESC')
    {
        $result = $this->createQueryBuilder('c')
            ->select('c, (SELECT COUNT(v1.id) FROM SpoutletBundle:CommentVote v1 WHERE v1.voteType=:up AND v1.comment=c) AS upvotes, (SELECT COUNT(v2.id) FROM SpoutletBundle:CommentVote v2 WHERE v2.voteType=:down AND v2.comment=c) AS downvotes')
            ->leftJoin('c.thread', 't')
            ->leftJoin('c.votes', 'v')
            ->andWhere('t.id = :thread')
            ->andWhere('c.parent IS NULL')
            ->andWhere('c.deleted <> true')
            ->setParameter('thread', $thread)
            ->setParameter('up', 'up')
            ->setParameter('down', 'down')
            ->addOrderBy('c.createdAt', $order)
            ->distinct('c.id')
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();

        return $result;
    }

    public function findCommentsForThreadSortedByVotes($thread, $limit=25)
    {
        $result = $this->createQueryBuilder('c')
            ->select('c, (SELECT COUNT(v1.id) FROM SpoutletBundle:CommentVote v1 WHERE v1.voteType=:up AND v1.comment=c) AS upvotes, (SELECT COUNT(v2.id) FROM SpoutletBundle:CommentVote v2 WHERE v2.voteType=:down AND v2.comment=c) AS downvotes')
            ->leftJoin('c.thread', 't')
            ->leftJoin('c.votes', 'v')
            ->andWhere('t.id = :thread')
            ->andWhere('c.parent IS NULL')
            ->andWhere('c.deleted <> true')
            ->setParameter('thread', $thread)
            ->setParameter('up', 'up')
            ->setParameter('down', 'down')
            ->addOrderBy('upvotes', 'DESC')
            ->addOrderBy('downvotes', 'ASC')
            ->addOrderBy('c.createdAt', 'DESC')
            ->distinct('c.id')
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();

        return $result;
    }

    public function findCommentsForThreadSortedByWithOffset($thread, $sort, $offset, $limit=25)
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c, (SELECT COUNT(v1.id) FROM SpoutletBundle:CommentVote v1 WHERE v1.voteType=:up AND v1.comment=c) AS upvotes, (SELECT COUNT(v2.id) FROM SpoutletBundle:CommentVote v2 WHERE v2.voteType=:down AND v2.comment=c) AS downvotes')
            ->leftJoin('c.thread', 't')
            ->leftJoin('c.votes', 'v')
            ->andWhere('t.id = :thread')
            ->andWhere('c.parent IS NULL')
            ->andWhere('c.deleted <> true')
            ->setParameter('thread', $thread)
            ->setParameter('up', 'up')
            ->setParameter('down', 'down')
            ->distinct('c.id')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        switch ($sort) {
            case 'votes':
                $qb->addOrderBy('upvotes', 'DESC')
                    ->addOrderBy('downvotes', 'ASC')
                    ->addOrderBy('c.createdAt', 'DESC');
                break;

            case 'recent':
                $qb->addOrderBy('c.createdAt', 'DESC');
                break;

            case 'oldest':
                $qb->addOrderBy('c.createdAt', 'ASC');
                break;

            default:
                die('invalid sort method supplied - '.$sort);
                break;
        }

        $result = $qb->getQuery()
            ->execute();

        return $result;
    }
}

