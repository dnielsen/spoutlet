<?php

namespace Platformd\CommentBundle\Controller;

use FOS\CommentBundle\Controller\ThreadController as BaseThreadController;

class ThreadController extends BaseThreadController
{
    public function showFlatAction($id, $sorter = null)
    {
        if ($cachedResult = $this->getCache()->get($id)) {
            return $cachedResult;
        }

        $result = parent::showFlatAction($id, $sorter);
        $this->getCache()->set($id, $result);

        return $result;
    }

    private function getCache()
    {
        return $this->container->get('platformd.cache');
    }
}

