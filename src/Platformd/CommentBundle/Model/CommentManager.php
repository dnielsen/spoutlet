<?php

namespace Platformd\CommentBundle\Model;

use FOS\CommentBundle\Entity\CommentManager as BaseCommentManager;
use Platformd\CommentBundle\Entity\Thread;

/**
 * Our custom comment manager
 */
class CommentManager extends BaseCommentManager
{
    /**
     * Returns an array of comments that match the thread prefix, with their
     * associated object attached
     *
     * array(
     *   0 => array('comment' => $comment, 'object' => $someDealObject),
     *   ...
     * )
     *
     * @param string $threadPrefix
     * @param int $count
     * @return array
     */
    public function findMostRecentCommentsByThreadPrefixWithObjects($threadPrefix, $count = 5)
    {
        /** @var $repo \Platformd\CommentBundle\Entity\CommentRepository */
        $repo = $this->repository;
        $comments = $repo->findMostRecentCommentsByThreadPrefix($threadPrefix, $count);

        $arr = array();
        foreach ($comments as $comment) {
            $obj = $this->getObjectFromThread($comment->getThread());

            if ($obj) {
                $arr[] = array(
                    'comment' => $comment,
                    'object'  => $obj,
                );
            }
        }

        return $arr;
    }

    /**
     * Attempts to get the source object from a comment, based on its thread-id
     *
     * todo - this will eventually need to be more elegant (aka, it's damned disaster...)
     *
     * @param Thread $thread
     * @return mixed
     */
    public function getObjectFromThread(Thread $thread)
    {
        $id = $thread->getId();

        // case news
        if (strpos($id, 'news-') === 0) {
            // this is a news item (news-zh-15)
            $pieces = explode('-', $id);
            if (count($pieces) != 3) {
                throw new \InvalidArgumentException('Invalid comment id format: '.$id);
            }

            $newsId = $pieces[2];
            $news = $this->em
                ->getRepository('NewsBundle:News')
                ->find($newsId)
            ;

            return $news;
        }

        // case deal
        if (strpos($id, 'deal-') === 0) {
            // this is a deal (deal-15)
            $pieces = explode('-', $id);
            if (count($pieces) != 2) {
                throw new \InvalidArgumentException('Invalid comment id format: '.$id);
            }

            $dealId = $pieces[1];
            $deal = $this->em
                ->getRepository('SpoutletBundle:Deal')
                ->find($dealId)
            ;

            return $deal;
        }

        // everything else is an abstract event and stores *just* the slug as the id
        $event = $this->em
            ->getRepository('SpoutletBundle:AbstractEvent')
            ->findOneBy(array('slug' => $id));

        return $event;
    }
}