<?php

namespace Platformd\UserBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

/**
 * The AWA Video site comes to us at various times with a query parameter
 * of "return". In some places - see SecurityController - we use that to
 * set a session variable called "awa_video_return_url".
 *
 * This class looks for that session variable and performs the redirect
 * under certain conditions (usually after login, logout).
 */
class AwaVideoLoginRedirectListener
{
    /**
     * The session parameter for the return URL is stored on this
     */
    const RETURN_SESSION_PARAMETER_NAME = 'awa_video_return_url';

    /**
     * We store the session id in a cookie with this name
     */
    const SESSION_ID_COOKIE_NAME = 'aw_session';

    private $securityContext;

    /**
     * @var \Symfony\Component\HttpKernel\Log\LoggerInterface
     */
    private $logger;

    public function __construct(SecurityContextInterface $context)
    {
        $this->securityContext = $context;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $session = $request->getSession();

        // check for "internal" routes, like the web debug toolbar
        // dont waste our requests on this stuff
        $route = $request->attributes->get('_route');
        $isInternal = (strpos($route, '_') === 0);

        if ($this->securityContext->isGranted('IS_AUTHENTICATED_FULLY')
            && $session->get(self::RETURN_SESSION_PARAMETER_NAME)
            && $request->isMethodSafe()
            && !$isInternal
            && !$request->isXmlHttpRequest()) {

            $this->log('redirecting back to return session parameter after login');

            $returnUrl = $session->get(self::RETURN_SESSION_PARAMETER_NAME);

            // remove the session key
            $session->set(
                self::RETURN_SESSION_PARAMETER_NAME,
                false
            );

            // set the redirect response
            $response = new RedirectResponse($returnUrl);
            $event->setResponse($response);
        } else {
            $this->log('no redirect to perform');
        }
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    private function log($msg)
    {
        if ($this->logger){
            $this->logger->debug($msg);
        }
    }
}