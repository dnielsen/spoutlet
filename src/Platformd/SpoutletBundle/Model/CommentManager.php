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
}
