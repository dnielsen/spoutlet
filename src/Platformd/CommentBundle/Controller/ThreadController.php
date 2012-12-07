<?php

namespace Platformd\CommentBundle\Controller;

use FOS\CommentBundle\Controller\ThreadController as BaseThreadController;

class ThreadController extends BaseThreadController
{
    public function showFlatPagedAction($page, $id, $sorter = null)
    {
        $thread = $this->getThread($id);
        $newCommentForm = $this->getCommentForm($thread);
        $replyForm = $this->getCommentForm($thread);

        return $this->container->get('templating')->renderResponse(
            'FOSCommentBundle:Thread:showFlat.html.'.$this->container->getParameter('fos_comment.template.engine'),
            array(
                'thread'           => $thread,
                'sorter'           => $sorter,
                'newCommentForm'   => $newCommentForm->createView(),
                'replyForm'        => $replyForm->createView(),
                'page' => $page
            )
        );
    }

    private function getCache()
    {
        return $this->container->get('platformd.cache.comment');
    }
}

