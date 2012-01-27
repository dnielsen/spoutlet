<?php

namespace Platformd\UserBundle\Security\Logout;

use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Platformd\CEVOBundle\CEVOAuthManager;
use Symfony\Component\HttpFoundation\Response;
use Platformd\CEVOBundle\Security\CEVO\CEVOAuthenticationListener;

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

    private $baseHost;

    public function __construct(CEVOAuthManager $cevoAuthManager, $baseHost)
    {
        $this->cevoAuthManager = $cevoAuthManager;
        $this->baseHost = $baseHost;
    }

    public function onLogoutSuccess(Request $request)
    {
        $returnUrl = $request->query->get('return', '/');

        // the httpUtils takes care to make it an absolute URL
        $redirectUrl =  $this->cevoAuthManager->generateCevoUrl(
            CEVOAuthManager::LOGOUT_PATH,
            $returnUrl
        );

        $response = new RedirectResponse($redirectUrl);

        $this->applyCookieRemovalHack($response);

        return $response;
    }

    /**
     * Contrary to what CEVO said, they do not destroy the cookie on logout.
     * After logout, we're still also able to API to them and get back information.
     *
     * So, we have to remove the cookie ourselves
     *
     * @param \Symfony\Component\HttpFoundation\Response $response
     */
    private function applyCookieRemovalHack(Response $response)
    {
        $cookieName = CEVOAuthenticationListener::COOKIE_NAME;

        $response->headers->clearCookie($cookieName, '/', $this->baseHost);
    }
}
