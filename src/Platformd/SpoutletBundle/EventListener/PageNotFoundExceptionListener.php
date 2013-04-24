<?php

namespace Platformd\SpoutletBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PageNotFoundExceptionListener
{
    private $router;

    public function __construct(RouterInterface $router) {
        $this->router = $router;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if (!$exception instanceof NotFoundHttpException) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();

        if (substr($path, -1, 1) != '/') {
            return;
        }

        $path = substr($path, 0, -1);

        try {
            $routeInfo = $this->router->match($path);
        } catch (ResourceNotFoundException $e) {
            return;
        }

        $url = $this->router->generate($routeInfo['_route']);
        $response = new RedirectResponse($url);
        $event->setResponse($response);
    }
}
