<?php

namespace Platformd\CEVOBundle\Security\CEVO;

use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Platformd\CEVOBundle\Security\CEVO\CEVOToken;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Platformd\CEVOBundle\Api\ApiException;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Platformd\SpoutletBundle\Model\LoginRecordManager;

/**
 * Security Listener "watches" for the CEVO cookie
 *
 * If it sees the CEVO cookie, it starts the authentication process
 */
class CEVOAuthenticationListener implements ListenerInterface
{
    /*
     * Cookie name used by CEVO to store the session id
     */
    const COOKIE_NAME = 'aw_session';

    protected $securityContext;
    protected $authenticationManager;
    protected $baseHost;
    protected $debug;
    protected $loginRecordManager;
    protected $router;
    protected $flashUtil;

    /**
     * This will be true in the test environment
     *
     * This allows there to be no cookie, but instead look for a ?username=
     * query parameter and use it.
     *
     * @var bool
     */
    protected $allowFakedAuth;

    /**
     * @var \Symfony\Component\HttpKernel\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager, $baseHost, LoginRecordManager $loginRecordManager, $router, $flashUtil, $debug = false, $allowFakedAuth = false)
    {
        $this->securityContext = $securityContext;
        $this->authenticationManager = $authenticationManager;
        $this->baseHost = $baseHost;
        $this->router = $router;
        $this->debug = $debug;
        $this->allowFakedAuth = $allowFakedAuth;
        $this->loginRecordManager = $loginRecordManager;
        $this->flashUtil = $flashUtil;
    }

    /**
     * Handles authentication from CEVO as the source
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     */
    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();



        $sessionString = $request->cookies->get(self::COOKIE_NAME);

        // allows us to fake the cookie in in the test environment
        if (!$sessionString && $this->allowFakedAuth) {
            $username = $request->query->get('username');
            if ($username) {
                $sessionString = sprintf('%s$abcdefg', $username);
            }
        }

        // an actual "logout" listener - listens to see if we're logged in, but the cookie is gone
        // don't do this in "Faked" auth land, where we don't really use cookies
        if (!$this->allowFakedAuth && $this->forceLogout($request, $sessionString)) {
            // actually log them out
            $request->getSession()->invalidate();
            $this->securityContext->setToken(null);

            return;
        }

        if (!$sessionString) {
            return;
        }

        // don't do anything if we're already correctly authenticated
        if ($this->securityContext->getToken() instanceof CEVOToken && $this->securityContext->getToken()->isAuthenticated()) {
            return;
        }

        // this effectively allows us to use switch user, but only in dev, just for safety of not messing up any other part of the process
        if ($this->debug && $this->securityContext->getToken() instanceof UsernamePasswordToken && $this->securityContext->getToken()->isAuthenticated()) {
            return;
        }

        list($userId, $sessionId) = self::splitSessionString($sessionString);
        $token = new CEVOToken($sessionId, $userId);

        // start logging errors (just for verbosity) of we failed on the previous attempt
        if ($request->getSession()->get('cevo_auth_error')) {
            $this->logError('About to try authentication after failing on the previous request');
        }

        // attempt authentication
        $response = $this->tryAuthentication($token, $request);

        // a true response means success
        if ($response === true) {
            return;
        }

        if ($response === 'forceLogout') {
            return;
        }

        $this->logError('CEVO Authentication FAILED on attempt #1');

        // if we got a non-true response, let's try one more time
        $response = $this->tryAuthentication($token, $request);
        if ($response === true) {
            $this->logError('CEVO Authentication PASSED on attempt #2');

            return;
        }

        if ($response === 'forceLogout') {
            return;
        }

        // we failed twice, so let's bail
        $this->logError('CEVO Authentication FAILED on attempt #2');
        $event->setResponse($response);
    }

    /**
     * Attempts to authenticate the user. This either:
     *
     * 1) Authenticates the user, setting the token on the security context
     * OR
     * 2) Returns a Response that's capable of handling the error
     *
     * @param CEVOToken $token
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|true
     * @throws \Symfony\Component\Security\Core\Exception\AuthenticationException
     */
    private function tryAuthentication(CEVOToken $token, Request $request)
    {
        try {

            if (strpos($request->getPathInfo(), 'forceLogout') === 1) {
                return 'forceLogout';
            }

            $returnValue = $this->authenticationManager->authenticate($token);

            if ($returnValue instanceof TokenInterface) {
                $this->securityContext->setToken($returnValue);
            } else {
                throw new AuthenticationException('Expected token, got back '.gettype($returnValue));
            }

            $this->loginRecordManager->recordLogin($returnValue, $request);

            return true;
        } catch (AuthenticationException $e) {
            // this might mean that the provider couldn't find a good token/user for me
            $response = $this->getResponseForAuthError($request, 'There was a problem authenticating you. Please contact the administrator');

            return $response;
        } catch (ApiException $e) {
            // this is what happens if CEVO chokes on the API

            // log the error
            $msg = 'Authentication error with CEVO. Message: '.$e->getMessage();
            $this->logError($msg);

            // if we're on debug, actually show the error
            if ($this->debug) {
                $response = $this->getResponseForAuthError($request, 'Authentication error with CEVO. Message: '.$e->getMessage());

                return $response;
            }

            // swallow the error in production
            return $this->getResponseForAuthError($request, false);
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
        // don't force logout a non-HTML request
        // this is important because it applies to the use API
        // The user API comes in with the session, but not with the cookies
        // we need to leave it alone in this case, let the session role
        if (strpos($request->getPathInfo(), '/api/users') === 0) {
            return;
        }

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
        if ($msg) {
            $this->flashUtil->setFlash('error', $msg);
        }

        // set a flag that says that this authentication failed
        $request->getSession()->set('cevo_auth_error', true);

        $forceLogoutUrl = $this->router->generate('force_logout', array('returnUrl' => urlencode($request->getUri())));

        $response = new RedirectResponse($forceLogoutUrl);

        return $response;
    }

    /**
     * The cookie value is a concatenation of the userid and session
     *
     * This splits those
     *
     * @static
     * @param $sessionString
     * @return array
     * @throws \Platformd\CEVOBundle\Api\ApiException
     */
    private static function splitSessionString($sessionString)
    {
        $pieces = explode('$', $sessionString);

        if (count($pieces) !=2) {
            throw new ApiException('Invalid session name set on cookie: '.$sessionString);
        }

        return $pieces;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Logs errors
     *
     * @param $msg
     */
    private function logError($msg)
    {
        if ($this->logger) {
            $this->logger->err($msg);
        }
    }
}
