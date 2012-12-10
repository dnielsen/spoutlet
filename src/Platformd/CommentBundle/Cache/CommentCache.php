<?php

namespace Platformd\CommentBundle\Cache;

use \Memcached;
use Platformd\CommentBundle\Entity\Thread;

class CommentCache
{
    private $memcached;
    private $keyPattern;

    public function __construct(Memcached $memcached, $keyPattern = null)
    {
        $this->memcached  = $memcached;
        $this->keyPattern = $keyPattern ?: 'comment_thread_html_non_paged:%s';
    }

    public function get($threadId)
    {
        return $this->memcached->get($this->getKey($threadId));
    }

    public function set($threadId, $content)
    {
        return $this->memcached->set($this->getKey($threadId), $content);
    }

    public function delete($threadId)
    {
        return $this->memcached->delete($this->getKey($threadId));
    }

    private function getKey($threadId)
    {
        return sprintf($this->keyPattern, $threadId);
    }
}

