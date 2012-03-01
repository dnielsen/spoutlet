<?php

namespace Platformd\UserBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Platformd\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\BrowserKit\Client;

class RegistrationControllerTest extends WebTestCase
{
    public function testRegistrationRedirect()
    {
        $client = self::createClient();

        $client->request('GET', '/account/register?return=/video/foo');
        $response = $client->getResponse();

        
        $this->assertTrue($response->isRedirect());
        $returnUrl = $client->getRequest()->getUriForPath('/video/foo');
        $targetUrl = 'http://localhost/cevo/api/stub/account/register?return='.urlencode($returnUrl);
        $this->assertEquals($targetUrl, $response->headers->get('Location'));
    }
}