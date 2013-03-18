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
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();

        return $result;
    }

    public function findCommentsForThreadSortedByVotesWithOffset($thread, $offset, $limit=25)
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
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();

        return $result;
    }

    public function findCommentsForGiveaways($site, $limit=8)
    {
        $result = $this->createQueryBuilder('c')
            ->leftJoin('c.thread', 't')
            ->orderBy('c.createdAt', 'DESC')
            ->where('t.permalink like :giveaways')
            ->andWhere('c.deleted <> true')
            ->andWhere('t.site = :site')
            ->setParameter('site', $site)
            ->setParameter('giveaways', '%' . 'giveaways' . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();

        return $result;
    }
}

