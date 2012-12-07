<?php

namespace Platformd\CommentBundle\Tests\Cache;

use Platformd\CommentBundle\Cache\CommentCache;

class CommentCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     **/
    public function should_generate_keys_for_thread()
    {
        $memcache = $this->getMock('Memcached');

        $memcache->expects($this->any())
            ->method('set')
            ->with('test_key_test')
        ;

        $cache = new CommentCache($memcache, 'test_key_%s');

        $cache->set('test', 'test');
    }
}

