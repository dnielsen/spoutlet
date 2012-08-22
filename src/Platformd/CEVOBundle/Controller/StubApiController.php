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
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

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
        $isTestEnv = $this->container->getParameter('kernel.environment') == 'test';

        $return = $request->query->get('return');

        // in the test environment, we redirect back with this, which actually triggers the auth
        if ($isTestEnv) {
            $return .= '?username='.self::FAKE_USER_ID;
        }

        $session = $request->getSession();

        $cookieName = CEVOAuthenticationListener::COOKIE_NAME;
        // CEVO uses this strange concatenation of user id and session
        $cookieValue = self::FAKE_USER_ID.'$'.$session->getId();

        $response = new Response();

        $host = $this->container->getParameter('base_host');

        switch ($action) {
            case 'login':
            case 'register':
                // getting inconsistent results, using both methods to set cookie
                if (!$isTestEnv) {
                    setcookie($cookieName, $cookieValue, null, '/', $host);
                }
                $cookie = new Cookie($cookieName, $cookieValue, 0, '/', $host, false, false);
                $response->headers->setCookie($cookie);
                $message = 'You are now authenticated';
                break;
            case 'logout':
                // getting inconsistent results, using both methods to set cookie
                if (!$isTestEnv) {
                    setcookie($cookieName, '', null, '/', $host);
                }
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

        if (!$isTestEnv) {
            // for some reason setting cookies is iffy, so totally hacking this
            echo $html;die;
        }

        $response->setContent($html);

        return $response;
    }

    /**
     * Fakes the CEVO api for getting details about the current user
     */
    public function getAuthenticatedUsersDetailsAction(Request $request)
    {
        $userId = $request->request->get('_user_id');

        if (!$userId) {
            throw new \Exception('The _user_id param was not sent!');
        }

        $isTestEnv = $this->container->getParameter('kernel.environment') == 'test';
        if (!$isTestEnv && $userId != self::FAKE_USER_ID) {
            throw new \Exception('You can only fake the one user in the test environment - sent '.$userId);
        }

        $user = $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('UserBundle:User')
            ->findOneBy(array('cevoUserId' => $userId))
        ;
        // default to our fake admin user
        $username = $user ? $user->getUsername() : 'admin';

        if ($request->request->get('_method') != ApiManager::METHOD_AUTH_USER_DETAILS) {
            throw new \Exception('The _method is not set or incorrect!');
        }

        $data = array(
            'user' => array(
                'user_id'       => $userId,
                'username'      => $username,
                'handle'        => $username,
                'avatar_url'    => 'http://alienwarearena.com/images/profile-default.png',
                'country'       => $user->getCountry(),
                'profile_url'   => 'http://profile.com',
                'dob'           => '1984-06-05',
                // this field is not in their API yet, but we need it to be
                //'email'         => 'user@user.com',
            )
        );

        return new Response(json_encode($data));
    }
}
