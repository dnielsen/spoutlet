<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Platformd\SpoutletBundle\Entity\Comment;
use Platformd\SpoutletBundle\Entity\Thread;

class CommentVoteRepository extends EntityRepository
{
    public function findByUserAndComment(User $user, Comment $comment)
    {
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.user = :user')
            ->andWhere('v.comment = :comment')
            ->setParameters(array(
                'user'      => $user,
                'answer'    => $answer,
            ));

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getVotesListByComment(Comment $comment)
    {
        $qb = $this->createQueryBuilder('v')
            ->where('v.comment = :comment')
            ->setParameter('comment', $comment);

        return $qb->getQuery()->getResult();
    }

    public function getVoteCountsForComment(Comment $comment)
    {
        $qb = $this->createQueryBuilder('v')
            ->leftJoin('v.comment', 'c')
            ->select('(SELECT COUNT(v1.id) FROM SpoutletBundle:CommentVote v1 WHERE v1.voteType=:up AND v1.comment = c) AS up, (SELECT COUNT(v2.id) FROM SpoutletBundle:CommentVote v2 WHERE v2.voteType=:down AND v2.comment = c) AS down')
            ->andWhere('v.comment = :comment')
            ->setParameters(array(
                'comment'   => $comment,
                'up'        => 'up',
                'down'      => 'down',
            ));

        $result = $qb->getQuery()->getResult();

        return $result[0];
    }

    /*
        Returns an array with comment_id as the key and up/down values for vote count, i.e $vote[1]['up'] for the upvotes for comment id 1.
    */
    public function getVoteCountsForCommentsByThread(Thread $thread)
    {
        $qb = $this->createQueryBuilder('v')
            ->leftJoin('v.comment', 'c')
            ->leftJoin('c.thread', 't')
            ->select('c.id, (SELECT COUNT(v1.id) FROM SpoutletBundle:CommentVote v1 WHERE v1.voteType=:up AND v1.comment = c) AS upVotes, (SELECT COUNT(v2.id) FROM SpoutletBundle:CommentVote v2 WHERE v2.voteType=:down AND v2.comment = c) AS downVotes')
            ->andWhere('c.thread = :thread')
            ->setParameters(array(
                'thread'    => $thread,
                'up'        => 'up',
                'down'      => 'down',
            ));

        $result = $qb->getQuery()->getResult();

        $votes = array();

        foreach ($result as $comment) {
            $votes[$comment['id']]['up']    = $comment['upVotes'];
            $votes[$comment['id']]['down']  = $comment['downVotes'];
        }

        return $votes;
    }
}
