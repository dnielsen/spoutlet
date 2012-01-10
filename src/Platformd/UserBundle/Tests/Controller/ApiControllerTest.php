<?php

namespace Platformd\UserBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DemoControllerTest extends WebTestCase
{
    public function testUsersDetails()
    {
        $client = static::createClient();

        $client->request('POST', '/api/users/details', array(
            'users' => 'user,  organizer '
        ));

        $this->assertTrue($client->getResponse()->isOk());
        $data = $client->getResponse()->getContent();
        $arr = json_decode($data, true);

        $expected = array(
            'username' => 'user',
            'handle'   => 'user',
            'country'  => null,
            'avatar_url' => '/images/profile-default.png',
            'profile_url' => '/account/profile/user',
        );
        $this->assertEquals(2, count($arr));
        $this->assertEquals($expected, array_shift($arr));
    }
}