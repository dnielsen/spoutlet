<?php

namespace Platformd\UserBundle\Security\Logout;

use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\HttpUtils;

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
            return new RedirectResponse($return);
        }

        return $this->httpUtils->createRedirectResponse($request, '/');
    }

}