<?php

namespace Platformd\UserBundle\Listener;

use Platformd\UserBundle\Exception\UserRegistrationTimeoutException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\EngineInterface;

/**
 * Handles listening and handling the UserRegistrationTimeoutException
 */
class UserRegistrationTimeoutListener
{
    private $router;
    private $templating;

    public function __construct(UrlGeneratorInterface $router, EngineInterface $templating)
    {
        $this->router       = $router;
        $this->templating   = $templating;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (!$event->getException() instanceof UserRegistrationTimeoutException) {
            return;
        }

        $url = $this->router->generate('fos_user_registration_register', array('timedout' => 1));

        $event->setResponse(new RedirectResponse($url));

        return;
    }
}
