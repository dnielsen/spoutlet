<?php

namespace Platformd\SpoutletBundle\Model;

use Platformd\SpoutletBundle\Entity\Thread;
use Platformd\SpoutletBundle\Entity\Comment;

class CommentManager
{
    private $threadRepo;
    private $linkableManager;
    private $em;
    private $avatarPathResolver;

    function __construct($threadRepo, $linkableManager, $em, $avatarPathResolver) {
        $this->threadRepo         = $threadRepo;
        $this->linkableManager    = $linkableManager;
        $this->em                 = $em;
        $this->avatarPathResolver = $avatarPathResolver;
    }

    public function checkThread($object)
    {
        $threadId         = $object->getThreadId();
        $thread           = $this->threadRepo->find($threadId);
        $correctPermalink = $this->linkableManager->link($object).'#comments';

        if (!$thread) {
            $thread = new Thread();
            $thread->setId($threadId);
            $thread->setPermalink($correctPermalink);

            $this->em->persist($thread);
            $this->em->flush();
        } else {

            if ($thread->getPermalink() != $correctPermalink) {
                $thread->setPermalink($correctPermalink);
                $this->em->persist($thread);
                $this->em->flush();
            }
        }

        return $thread->getPermalink();
    }

    public function findMostRecentCommentsByThreadPrefixWithObjects($threadPrefix, $count = 5)
    {
        $repo = $this->em->getRepository('SpoutletBundle:Comment');
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
                ->getRepository('GiveawayBundle:Deal')
                ->find($dealId)
            ;

            return $deal;
        }

        // case group
        if (strpos($id, 'group-') === 0) {
            $pieces = explode('-', $id);
            if (count($pieces) != 2) {
                throw new \InvalidArgumentException('Invalid comment id format: '.$id);
            }

            $groupId = $pieces[1];
            $group = $this->em
                ->getRepository('GroupBundle:Group')
                ->find($groupId)
            ;

            return $group;
        }

        // case group news article
        if (strpos($id, 'group_news-') === 0) {
            $pieces = explode('-', $id);
            if (count($pieces) != 2) {
                throw new \InvalidArgumentException('Invalid comment id format: '.$id);
            }

            $newsArticleId = $pieces[1];
            $newsArticle = $this->em
                ->getRepository('GroupBundle:GroupNews')
                ->find($newsArticleId)
            ;

            return $newsArticle;
        }

        // case group video
        if (strpos($id, 'group_video-') === 0) {
            $pieces = explode('-', $id);
            if (count($pieces) != 2) {
                throw new \InvalidArgumentException('Invalid comment id format: '.$id);
            }

            $videoArticleId = $pieces[1];
            $video = $this->em
                ->getRepository('GroupBundle:GroupVideo')
                ->find($videoArticleId)
            ;

            return $video;
        }

        // everything else is an abstract event and stores *just* the slug as the id
        $event = $this->em
            ->getRepository('SpoutletBundle:AbstractEvent')
            ->findOneBy(array('slug' => $id));

        return $event;
    }

    public function getCommentData($thread, $sort='recent')
    {
        $commentRepo = $this->em->getRepository('SpoutletBundle:Comment');
        $commentsArr = $commentRepo->getCommentsForThreadSortedByQuery($thread, $sort);
        $comments    = array();

        foreach ($commentsArr as $comment) {

            $data    = array();

            $data['id']                  = $comment['id'];
            $data['deleted']             = $comment['deleted'];
            $data['createdAt']           = $comment['createdAt'];
            $data['body']                = nl2br($comment['body']);
            $data['parentId']            = $comment['parentId'];
            $data['authorId']            = $comment['authorId'];
            $data['authorUuid']          = $comment['authorUuid'];
            $data['authorUsername']      = $comment['authorUsername'];
            $data['upVoteCount']         = (int) $comment['upVoteCount'];
            $data['downVoteCount']       = (int) $comment['downVoteCount'];
            $data['publishedReplyCount'] = (int) $comment['publishedReplyCount'];
            $data['points']              = (int) number_format($data['upVoteCount'] - $data['downVoteCount']);
            $data['authorAccountLink']   = sprintf('http://www.alienwarearena.com/member/%s', $data['authorUuid']);
            $data['hasMoreReplies']      = $data['publishedReplyCount'] > 3;
            $data['isHidden']            = false;
            $data['isFirstReply']        = false;
            $data['replies']             = array();

            $hasAvatar            = $comment['avatarId'];
            $data['authorAvatar'] = $hasAvatar ? ($data['parentId'] ? $this->avatarPathResolver->getPathFromParams($data['authorUuid'], 32) : $this->avatarPathResolver->getPathFromParams($data['authorUuid'])) : null;

            if ($data['parentId']) {
                if (isset($comments[$data['parentId']])) {
                     if (count($comments[$data['parentId']]['replies']) > 2) {
                        $data['isHidden'] = true;
                    }

                    if (count($comments[$data['parentId']]['replies']) == 0) {
                        $data['isFirstReply'] = true;
                    }
                }

                $comments[$data['parentId']]['replies'][] = $data;
            } else {
                if (isset($comments[$data['id']])) {
                    $data['replies'] = $comments[$data['id']]['replies'];
                }

                $comments[$data['id']] = $data;
            }
        }

        $return = array();

        foreach ($comments as $comment) {
            $return[] = $comment;
        }

        return $return;
    }
}
