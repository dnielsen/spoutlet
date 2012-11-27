<?php

namespace Platformd\CommentBundle\Controller;

use FOS\CommentBundle\Controller\ThreadController as BaseThreadController;

class ThreadController extends BaseThreadController
{
    public function showFlatAction($id, $sorter = null)
    {
        if ($cachedResponse = $this->getCache()->get($id)) {
            return $cachedResponse;
        }

        $response = parent::showFlatAction($id, $sorter);
        $this->getCache()->set($id, $response);

        return $response;
    }

    private function getCache()
    {
        return $this->container->get('platformd.cache.comment');
    }
}

