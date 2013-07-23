<?php

namespace Platformd\SpoutletBundle\Age;

use Platformd\SpoutletBundle\Exception\InsufficientAgeException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Platformd\SpoutletBundle\Age\AgeManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\EngineInterface;

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

    private $ageManager;

    private $templating;

    public function __construct(UrlGeneratorInterface $router, AgeManager $ageManager, EngineInterface $templating)
    {
        $this->router     = $router;
        $this->ageManager = $ageManager;
        $this->templating = $templating;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (!$event->getException() instanceof InsufficientAgeException) {
            return;
        }

        if ($this->ageManager->isUsersAgeVerified()) {
            $content = $this->templating->render(
                'SpoutletBundle:Age:insufficientAge.html.twig'
            );

            $event->setResponse(new Response($content));

            return;
        }

        if ($event->getRequest()->get('_route') != 'fos_user_registration_register') {
            $returnUrl = $event->getRequest()->getUri();
            $verifyUrl = $this->router->generate('age_verification', array('returnUrl' => urlencode($returnUrl)));
            $response  = new RedirectResponse($verifyUrl);

            $event->setResponse($response);
        }
    }
}
