<?php

namespace Platformd\UserBundle\Security\Logout;

use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Platformd\CEVOBundle\CEVOAuthManager;


/**
 * Custom logout success handler to handle our special log:
 *
 * * When the user logs out, we will actually redirect to CEVO's log out
 *      page with a ?return= at the end back to our site
 *
 * * If we already have a ?return query parameter, then we need to use it,
 *      but we need to transform it to be absolute if not already
 */
class SuccessHandler implements LogoutSuccessHandlerInterface
{
    private $cevoAuthManager;

    public function __construct(CEVOAuthManager $cevoAuthManager)
    {
        $this->cevoAuthManager = $cevoAuthManager;
    }

    public function onLogoutSuccess(Request $request)
    {
        $returnUrl = $request->query->get('return', '/');

        // the httpUtils takes care to make it an absolute URL
        $redirectUrl =  $this->cevoAuthManager->generateCevoUrl(
            CEVOAuthManager::LOGOUT_PATH,
            $returnUrl
        );

        return new RedirectResponse($redirectUrl);
    }

}
