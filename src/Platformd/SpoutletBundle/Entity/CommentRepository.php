<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;

class CommentRepository extends EntityRepository
{
    public function getFindCommentsForUserQuery($user, $dateSort = 'DESC')
    {
       return $this->createQueryBuilder('c')
            ->andWhere('c.author = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', $dateSort)
            ->getQuery()
        ;
    }

    public function getAllActiveCommentsForUserQuery($user)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.author = :user')
            ->andWhere('c.deleted = false')
            ->setParameter('user', $user)
            ->getQuery()
        ;
    }

    private function findCommentsForSiteFeature($limit, $featureName)
    {
        $result = $this->createQueryBuilder('c')
            ->leftJoin('c.thread', 't')
            ->orderBy('c.createdAt', 'DESC')
            ->where('t.permalink like :featureName')
            ->andWhere('c.deleted <> true')
            ->setParameter('featureName', '%' . $featureName . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();

        return $result;
    }

    public function findMostRecentCommentsByThreadPrefix($prefix, $count = 5)
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.thread', 't')
            ->andWhere('t.id LIKE :prefix')
            ->setParameter('prefix', $prefix.'%')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($count)
            ->getQuery()
            ->execute()
        ;
    }

    public function getMetricsStats($from, $to)
    {
        $displayNames = array(
            'deal_count' => 'Deal Comments',
            'giveaway_count' => 'Giveaway Comments',
            'group_count' => 'Group Comments',
            'sweepstakes_count' => 'Sweepstakes Comments',
            'gallery_image_count' => 'Gallery Image Comments',
            'news_count' => 'News Comments',
            'group_event_count' => 'Group Event Comments',
            'global_event_count' => 'Event Comments',
            'video_count' => 'Video Comments',
            'group_discussion_post_count' => 'Group Discussion Posts',
        );

        $commentFrom = $from ? ' AND created_at >= "'.$from->setTime(0,0,0)->format('Y-m-d H:i:s').'"' : '';
        $commentTo   = $to   ? ' AND created_at <= "'.$to->setTime(23,59,59)->format('Y-m-d H:i:s').'"'   : '';

        $discussionFrom = $from ? ' AND created >= "'.$from->setTime(0,0,0)->format('Y-m-d H:i:s').'"' : '';
        $discussionTo   = $to   ? ' AND created <= "'.$to->setTime(23,59,59)->format('Y-m-d H:i:s').'"'   : '';

        $query = 'SELECT deal_comments.deal_count, group_comments.group_count, giveaway_comments.giveaway_count, sweepstakes_comments.sweepstakes_count,
                        gallery_image_comments.gallery_image_count, news_comments.news_count, group_event_comments.group_event_count, global_event_comments.global_event_count,
                        video_comments.video_count, group_discussion_posts.group_discussion_post_count
                    FROM
                    (SELECT count(*) deal_count FROM commenting_comment WHERE thread_id LIKE "deal-%"'.$commentFrom.$commentTo.') deal_comments,
                    (SELECT count(*) giveaway_count FROM commenting_comment WHERE thread_id LIKE "giveaway-%"'.$commentFrom.$commentTo.') giveaway_comments,
                    (SELECT count(*) group_count FROM commenting_comment WHERE thread_id LIKE "group-%"'.$commentFrom.$commentTo.') group_comments,
                    (SELECT count(*) sweepstakes_count FROM commenting_comment WHERE thread_id LIKE "sweepstake-%"'.$commentFrom.$commentTo.') sweepstakes_comments,
                    (SELECT count(*) gallery_image_count FROM commenting_comment WHERE thread_id LIKE "gallery_image-%"'.$commentFrom.$commentTo.') gallery_image_comments,
                    (SELECT count(*) news_count FROM commenting_comment WHERE thread_id LIKE "news-%"'.$commentFrom.$commentTo.') news_comments,
                    (SELECT count(*) group_event_count FROM commenting_comment WHERE thread_id LIKE "group-event-%"'.$commentFrom.$commentTo.') group_event_comments,
                    (SELECT count(*) global_event_count FROM commenting_comment WHERE thread_id LIKE "global-event-%"'.$commentFrom.$commentTo.') global_event_comments,
                    (SELECT count(*) video_count FROM commenting_comment WHERE thread_id LIKE "youtube-%"'.$commentFrom.$commentTo.') video_comments,
                    (SELECT count(*) group_discussion_post_count FROM pd_group_discussion_post WHERE id > 0'.$discussionFrom.$discussionTo.') group_discussion_posts';


        $stmt = $this->getEntityManager()
            ->getConnection()
            ->prepare($query);

        $stmt->execute();
        $commentData = $stmt->fetchAll();

        $data = array();

        foreach ($commentData[0] as $name => $count) {
            if (isset($displayNames[$name])) {
                $data[$displayNames[$name]] = $count;
            }
        }

        return $data;
    }

    public function getCommentCountByThread($thread, $fromDate=null, $thruDate=null)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('COUNT(c.id)');
        $qb->where('c.thread = :thread');
        $qb->setParameter('thread', $thread);

        if($fromDate != null and $thruDate != null)
        {
            $qb->andWhere('c.createdAt >= :fromDate')
               ->andWhere('c.createdAt <= :thruDate')
               ->setParameter('fromDate', $fromDate)
               ->setParameter('thruDate', $thruDate);
        }
        try {
        $total = $qb->getQuery()->getSingleScalarResult();
        }
        catch (NoResultException $e) {
            return 0;
        }

        return $total;
    }

    public function getCommentsForThreadSortedByQuery($thread)
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c, (SELECT COUNT(v1.id) FROM SpoutletBundle:CommentVote v1 WHERE v1.voteType=:up AND v1.comment=c) AS upvotes, (SELECT COUNT(v2.id) FROM SpoutletBundle:CommentVote v2 WHERE v2.voteType=:down AND v2.comment=c) AS downvotes, u.id, u.uuid, u.username, r')
            ->leftJoin('c.thread', 't')
            ->leftJoin('c.parent', 'p')
            ->leftJoin('c.votes', 'v')
            ->leftJoin('c.author', 'u')
            ->leftJoin('c.replies', 'r')
            ->andWhere('t.id = :thread')
            ->andWhere('c.deleted <> true')
            ->andWhere('p.deleted <> true OR p IS NULL')
            ->setParameter('thread', $thread->getId())
            ->setParameter('up', 'up')
            ->setParameter('down', 'down')
            ->distinct('c.id');

        return $qb->getQuery()->getResult();
    }
}

