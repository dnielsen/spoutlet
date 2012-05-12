<?php

namespace Platformd\SpoutletBundle\Age;

use Platformd\SpoutletBundle\Exception\InsufficientAgeException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Handles listening and handling the InsufficientAgeException
 */
class InsufficientAgeListener
{
    /**
     * Key used in the session to store the target path
     */
    const TARGET_PATH_KEY = '_age_verify_target';

    private $router;

    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Handles the kernel.exception event
     *
     * The point is to redirect the user to the "choose your birthday" page
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (!$event->getException() instanceof InsufficientAgeException) {
            return;
        }

        $session = $event->getRequest()->getSession();
        $request = $event->getRequest();

        // set the target path for after the age verify
        $session->set(self::TARGET_PATH_KEY, $request->getUri());

        // now redirect the user
        $verifyUrl = $this->router->generate('age_verification');
        $response = new RedirectResponse($verifyUrl);

        $event->setResponse($response);
    }

}