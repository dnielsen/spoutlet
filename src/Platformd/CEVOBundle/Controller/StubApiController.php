<?php

namespace Platformd\CEVOBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Platformd\CEVOBundle\Security\CEVO\CEVOAuthenticationListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Platformd\UserBundle\Entity\User;
use Platformd\CEVOBundle\Api\ApiManager;

/**
 * A controller that "fakes" the CEVO API in development only
 */
class StubApiController extends Controller
{
    const FAKE_USER_ID = 55;

    /**
     * Fakes the login, logout, and register pages of CEVO's API
     *
     * @param $action
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Exception
     */
    public function stubEndpointAction($action, Request $request)
    {
        $return = $request->query->get('return');
        $session = $request->getSession();

        $cookieName = CEVOAuthenticationListener::COOKIE_NAME;
        // CEVO uses this strange concatenation of user id and session
        $cookieValue = self::FAKE_USER_ID.'%'.$session->getId();

        $response = new Response();

        $host = $this->container->getParameter('base_host');
        // sanity check
        if (strpos($request->getHttpHost(), $host) === false) {
            throw new \InvalidArgumentException('Base host is not valid for the current host: '. $request->getHttpHost());
        }

        switch ($action) {
            case 'login':
            case 'register':
                // getting inconsistent results, using both methods to set cookie
                setcookie($cookieName, $cookieValue, null, '/', $host);
                $cookie = new Cookie($cookieName, $cookieValue, 0, '/', $host, false, false);
                $response->headers->setCookie($cookie);
                $message = 'You are now authenticated';
                break;
            case 'logout':
                // getting inconsistent results, using both methods to set cookie
                setcookie($cookieName, '', null, '/', $host);
                $response->headers->clearCookie($cookieName, '/', $host);
                $message = 'You are now logged out';
                break;
            default:
                throw new \Exception(sprintf('Unknown action "%s"', $action));
        }

        $html = $this->renderView('CEVOBundle:StubApi:status.html.twig', array(
            'message' => $message,
            'return' => $return,
        ));

        // for some reason setting cookies is iffy, so totally hacking this
        echo $html;die;

        $response->setContent($html);

        return $response;
    }

    /**
     * Fakes the CEVO api for getting details about the current user
     */
    public function getAuthenticatedUsersDetailsAction(Request $request)
    {
        if ($request->request->get('_user_id') != self::FAKE_USER_ID) {
            throw new \Exception('The _user_id param was not sent or is wrong!');
        }

        if ($request->request->get('_method') != ApiManager::METHOD_AUTH_USER_DETAILS) {
            throw new \Exception('The _method is not set or incorrect!');
        }

        $data = array(
            'id' => self::FAKE_USER_ID,
            'username'      => 'user',
            'handle'        => 'user',
            'avatar_url'    => 'http://avatar.com',
            'country'       => 'japan',
            'profile_url'   => 'http://profile.com',
            // this field is not in their API yet, but we need it to be
            //'email'         => 'user@user.com',
        );

        return new Response(json_encode($data));
    }
}