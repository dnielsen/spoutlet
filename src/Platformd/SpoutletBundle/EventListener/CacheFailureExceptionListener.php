<?php

namespace Platformd\SpoutletBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Platformd\SpoutletBundle\Exception\CacheFailureException;
use Symfony\Component\Templating\EngineInterface;

class CacheFailureExceptionListener
{
    private $templating;

    public function __construct(EngineInterface $templating) {
        $this->templating = $templating;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if (!$exception instanceof CacheFailureException) {
            return;
        }

        $content =
            $this->templating->render('SpoutletBundle::error.html.twig',
                array(
                    'title' => 'platformd.cache.currently_under_heavy_load_title',
                    'body'  => 'platformd.cache.currently_under_heavy_load_body'));

        $response = new Response($content);

        if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());
        } else {
            $response->setStatusCode(500);
        }

        $event->setResponse($response);
    }
}

