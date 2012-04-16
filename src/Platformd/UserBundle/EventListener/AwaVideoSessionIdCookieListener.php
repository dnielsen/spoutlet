<?php

namespace Platformd\UserBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Platformd\UserBundle\EventListener\AwaVideoLoginRedirectListener;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Handles storing a cookie with the session id based on authentication
 *
 * When the user is authenticated, then we need to store the session id on
 * a special cookie so that other sites on our subdomain can pick up that
 * cookie and send API calls back using it to authenticate.
 *
 * This checks the authentication status and sets or clears that cookie accordingly.
 *
 */
class AwaVideoSessionIdCookieListener
{
    private $securityContext;

    private $baseHost;

    /**
     * This will be true in the test environment
     *
     * This allows there to be no cookie, but instead look for a ?username=
     * query parameter and use it.
     *
     * @var bool
     */
    private $allowFakedAuth;

    public function __construct(SecurityContextInterface $context, $baseHost, $allowFakedAuth = false)
    {
        $this->securityContext = $context;
        $this->baseHost = $baseHost;
        $this->allowFakedAuth = $allowFakedAuth;
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        /**
         * Without this, asking isGranted() activates parts of the security
         * context that kill the login process. So, we have to be careful.
         */
        if (!$this->securityContext->getToken()) {
            return;
        }

        $response = $event->getResponse();
        if ($this->securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            // set the needed aw_session cookie used on the other end
            $cookieVal = $event->getRequest()->getSession()->getId();
        } else {
            // make sure the cookie is cleared out
            $cookieVal = '';
        }

        // for some reason the cookie-setting below kills our faked authentication
        // that is accomplished in CEVOAuthenticationListener
        if ($this->allowFakedAuth) {
            return;
        }

        $cookie = new Cookie(
            AwaVideoLoginRedirectListener::SESSION_ID_COOKIE_NAME,
            $cookieVal,
            0,
            '/',
            $this->baseHost
        );
        $response->headers->setCookie($cookie);
    }
}