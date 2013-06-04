<?php

namespace Platformd\SpoutletBundle\Model;

use Platformd\SpoutletBundle\Entity\Thread;

class CommentManager
{
    private $threadRepo;
    private $linkableManager;
    private $em;

    function __construct($threadRepo, $linkableManager, $em) {
        $this->threadRepo       = $threadRepo;
        $this->linkableManager  = $linkableManager;
        $this->em               = $em;
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
}
