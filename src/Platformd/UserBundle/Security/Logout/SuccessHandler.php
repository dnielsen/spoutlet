<?php

namespace Platformd\UserBundle\Security\Logout;

use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Custom logout success handler so we can redirect to the video site if needed
 */
class SuccessHandler implements LogoutSuccessHandlerInterface
{
    private $httpUtils;

    public function __construct(HttpUtils $httpUtils)
    {
        $this->httpUtils = $httpUtils;
    }

    public function onLogoutSuccess(Request $request)
    {
        if ($return = $request->query->get('return')) {
            $response = new RedirectResponse($return);

            // remove cookie so that video site doesn't keep API'ing to us to try to auth
            $cookie = new Cookie('aw_session', '');
            $response->headers->setCookie($cookie);

            return $response;
        }

        return $this->httpUtils->createRedirectResponse($request, '/');
    }

}
