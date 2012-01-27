<?php

namespace Platformd\CEVOBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Platformd\UserBundle\EventListener\AwaVideoLoginRedirectListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Platformd\UserBundle\Entity\User;

/**
 * A controller that "fakes" the CEVO API in development only
 */
class StubApiController extends Controller
{
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

        $cookieName = AwaVideoLoginRedirectListener::SESSION_ID_COOKIE_NAME;

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
                setcookie($cookieName, $session->getId(), null, '/');
                $cookie = new Cookie($cookieName, $session->getId(), 0, '/', null, false, false);
                $response->headers->setCookie($cookie);
                $message = 'You are now authenticated';
                break;
            case 'logout':
                // getting inconsistent results, using both methods to set cookie
                setcookie($cookieName, '', null, '/');
                $response->headers->clearCookie($cookieName, '/');
                $message = 'You are now logged out';
                break;
            default:
                throw new \Exception(sprintf('Unknown action "%s"', $action));
        }

        $html = $this->renderView('CEVOBundle:StubApi:status.html.twig', array(
            'message' => $message,
            'return' => $return,
        ));

        echo $html;die;

        $response->setContent($html);

        return $response;
    }

    /**
     * Fakes the CEVO api for getting details about the current user
     */
    public function getAuthenticatedUsersDetailsAction()
    {
        $user = $this->getUserManager()->findUserByEmail('user@user.com');

        if (!$user) {
            $user = $this->getUserManager()->createUser();
            $user->setUsername('user');
            $user->setPassword('stub');
            $user->setEmail('user@user.com');
            $this->getUserManager()->updateUser($user);
        }

        $data = array(
            'id' => $user->getId(),
            'username'      => $user->getUsername(),
            'handle'        => $user->getUsername(),
            'avatar_url'    => 'http://avatar.com',
            'country'       => 'japan',
            'profile_url'   => 'http://profile.com',
            // this field is not in their API yet, but we need it to be
            'email'         => $user->getEmail(),
        );

        return new Response(json_encode($data));
    }
}