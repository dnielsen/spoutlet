<?php

namespace Knp\MediaBundle\Listener;

use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Knp\MediaBundle\Exception\UniqueFilenameGenerationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * An exception listener which tries to give some information to the user
 * about the UniqueFilenameGenerationException being thrown
 */
class UniqueFilenameGenerationExceptionListener
{
    protected $templating;
    protected $debug;

    public function __construct(EngineInterface $templating, $debug)
    {
        $this->templating = $templating;
        $this->debug = $debug;
    }

    /**
     * Listens on kernel.exception
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
     * @return mixed
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (!$event->getException() instanceof UniqueFilenameGenerationException) {
            return;
        }

        // does nothing on debug, let the true exception show
        if ($this->debug) {
            return;
        }

        $content = $this->templating->render('KnpMediaBundle:Exception:uniqueFilenameGenerationException.html.twig', array(
            'exception' => $event->getException(),
        ));

        $response = new Response($content, 500);
        $event->setResponse($response);
    }
}
