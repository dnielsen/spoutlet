<?php

namespace Platformd\CommentBundle\Controller;

use FOS\CommentBundle\Controller\ThreadController as BaseThreadController;

class ThreadController extends BaseThreadController
{
    public function showFlatAction($id, $sorter = null)
    {
        return parent::showFlatAction($id, $sorter);

        // @TODO re-enable per user cache ( to avoid rendering user specific delete links for example)
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

