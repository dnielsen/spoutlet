<?php

namespace Platformd\UserBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Platformd\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\BrowserKit\Client;

class SuccessHandlerTest extends WebTestCase
{
    public function testNormalLogout()
    {
        $client = self::createClient();

        $client->request('GET', '/logout');

        $response = $client->getResponse();
        $this->assertTrue($response->isRedirect());

        // test a normal logout
        $returnUrl = $client->getRequest()->getUriForPath('/');
        $targetUrl = 'http://localhost/cevo/api/stub/cmd/account/logout?return='.urlencode($returnUrl);
        $this->assertEquals($targetUrl, $response->headers->get('Location'));

        // test a logout that already has a ?return=
        $client->request('GET', '/logout?return=/video/foo');

        $response = $client->getResponse();
        $this->assertTrue($response->isRedirect());

        // test a normal logout
        $returnUrl = $client->getRequest()->getUriForPath('/video/foo');
        $targetUrl = 'http://localhost/cevo/api/stub/cmd/account/logout?return='.urlencode($returnUrl);
        $this->assertEquals($targetUrl, $response->headers->get('Location'));
    }
}