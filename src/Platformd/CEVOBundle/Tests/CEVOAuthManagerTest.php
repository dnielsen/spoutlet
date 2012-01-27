<?php

namespace Platformd\UserBundle\Security\Logout;

use Platformd\CEVOBundle\CEVOAuthManager;

class CEVOAuthManagerTest extends \PHPUnit_Framework_TestCase
{
    // constants used for testing
    static private $cevoURL = 'http://cevo.com';
    static private $localUrl = 'http://foo.com';

   /**
    * @dataProvider provideUrlParameters
    * @param $path
    * @param $locale
    * @param $returnURL
    * @param $expected
    */
    public function testGenerateCEVOUrl($path, $locale, $returnURL, $expected)
    {
        // stub the session
        $session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $session->expects($this->any())
            ->method('getLocale')
            ->will($this->returnValue($locale))
        ;

        // stub the request
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $request->expects($this->any())
            ->method('getUriForPath')
            ->will($this->returnValue(self::$localUrl.$returnURL))
        ;

        // mocked, but not used - getSession and getRequest are mocked instead
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerInterface')
            ->getMock()
        ;

        // also mocking the auth manager itself, so we can control the internal request and session methods
        $authManager = $this->getMockBuilder('Platformd\CEVOBundle\CEVOAuthManager')
            ->setMethods(array('getSession', 'getRequest'))
            ->setConstructorArgs(array(self::$cevoURL, $container))
            ->getMock()
        ;
        $authManager->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($request))
        ;
        $authManager->expects($this->any())
            ->method('getSession')
            ->will($this->returnValue($session))
        ;

        $result = $authManager->generateCevoUrl($path, $returnURL);
        $this->assertEquals($result, $expected);
    }

    public function provideUrlParameters()
    {
        return array(
            array('/logout', 'en', '/foo-page', self::$cevoURL.'/logout?return='.urlencode(self::$localUrl.'/foo-page')),
            array('/logout', 'zh', '/foo-page', self::$cevoURL.'/china/logout?return='.urlencode(self::$localUrl.'/foo-page')),
            array('/logout', 'zh', 'http://www.google.com', self::$cevoURL.'/china/logout?return='.urlencode('http://www.google.com')),
            array('/logout', 'zh', false, self::$cevoURL.'/china/logout'),
        );
    }
}
