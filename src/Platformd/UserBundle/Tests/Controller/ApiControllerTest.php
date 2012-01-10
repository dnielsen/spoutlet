<?php

namespace Platformd\UserBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Platformd\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\BrowserKit\Client;

class DemoControllerTest extends WebTestCase
{
    public function testUsersDetails()
    {
        $client = static::createClient();

        $client->request('POST', '/api/users/details.json', array(
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

    public function testAuthenticatedUserDetails()
    {
        $client = static::createClient();

        $client->request('GET', '/api/users/current/details.json');
        $this->assertTrue($client->getResponse()->isOk());
        $data = $client->getResponse()->getContent();
        $arr = json_decode($data, true);

        // user is not logged in!
        $this->assertEquals(true, $arr['error']);

        // auth the user
        $this->markTestIncomplete('authentication not currently working yet');
        $this->authenticateUser($client, 'user');

        $client->request('GET', '/api/users/current/details.json');
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

        $this->assertEquals($expected, $arr);
    }

    /**
     * Authenticates the user directly
     *
     * @param $username
     */
    private function authenticateUser(Client $client, $username)
    {
        $client->request('POST', '/login_check', array(
            '_username' => $username,
            '_password' => $username,
        ));
    }
}