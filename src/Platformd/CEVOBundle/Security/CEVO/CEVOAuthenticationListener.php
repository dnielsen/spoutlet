<?php

namespace Platformd\CEVOBundle\Security\CEVO;

use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Platformd\UserBundle\EventListener\AwaVideoLoginRedirectListener;
use Platformd\CEVOBundle\Security\CEVO\CEVOToken;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Platformd\CEVOBundle\Api\ApiException;

/**
 * Security Listener "watches" for the CEVO cookie
 *
 * If it sees the CEVO cookie, it starts the authentication process
 */
class CEVOAuthenticationListener implements ListenerInterface
{
    protected $securityContext;
    protected $authenticationManager;
    protected $baseHost;
    protected $debug;

    public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager, $baseHost, $debug = false)
    {
        $this->securityContext = $securityContext;
        $this->authenticationManager = $authenticationManager;
        $this->baseHost = $baseHost;
        $this->debug = $debug;
    }

    /**
     * Handles authentication from CEVO as the source
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     */
    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        $sessionId = $request->cookies->get(AwaVideoLoginRedirectListener::SESSION_ID_COOKIE_NAME);

        // an actual "logout" listener - listens to see if we're logged in, but the cookie is gone
        if ($this->forceLogout($request, $sessionId)) {
            return new RedirectResponse($request->getUri());
        }

        if (!$sessionId) {
            return;
        }

        // don't do anything if we're already correctly authenticated
        if ($this->securityContext->getToken() instanceof CEVOToken) {
            return;
        }

        $token = new CEVOToken($sessionId);

        try {
            $returnValue = $this->authenticationManager->authenticate($token);

            if ($returnValue instanceof TokenInterface) {
                $this->securityContext->setToken($returnValue);
            } else {
                throw new AuthenticationException('Expected token, got back '.gettype($returnValue));
            }
        } catch (AuthenticationException $e) {
            // this might mean that the provider couldn't find a good token/user for me
            $response = $this->getResponseForAuthError($request, 'There was a problem authenticating you. Please contact the administrator');

            $event->setResponse($response);
        } catch (ApiException $e) {
            // this is what happens if CEVO chokes on the API
            if ($this->debug) {
                //throw $e;
            }

            $response = $this->getResponseForAuthError($request, 'There was a problem authenticating you (API error). Please contact the administrator');

            $event->setResponse($response);
        }
    }

    /**
     * Whether or not the user is CEVO auth'ed, but should not be
     *
     * This checks to see if the CEVO cookie is still there. If it is not,
     * but we're CEVO logged in, we return true, to force a logout
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param $sessionId
     * @return bool
     */
    private function forceLogout(Request $request, $sessionId)
    {
        $currentToken = $this->securityContext->getToken();

        return ($currentToken && $currentToken instanceof CEVOToken && !$sessionId);
    }

    /**
     * When authentication fails, this resets everything, returns a redirect response
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param $msg
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function getResponseForAuthError(Request $request, $msg)
    {
        $request->getSession()->setFlash('error', $msg);

        // to prevent things from totally freaking out, getting in a loop on this failure
        // we need to return a response that removes the cookie
        $response = new RedirectResponse($request->getUriForPath('/'));
        $response->headers->clearCookie(AwaVideoLoginRedirectListener::SESSION_ID_COOKIE_NAME, '/');

        return $response;
    }
}