<?php

namespace Platformd\SpoutletBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\HttpKernel;

class PageNotFoundExceptionListener
{
    private $router;

    public function __construct(RouterInterface $router) {
        $this->router = $router;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (HttpKernel::MASTER_REQUEST != $event->getRequestType()) {
            // don't do anything if it's not the master request
            return;
        }

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

        // Prevent redirection loop from Symfony re-adding a trailing slash
        if ($routeInfo['_controller'] == 'Symfony\Bundle\FrameworkBundle\Controller\RedirectController::urlRedirectAction') {
            return;
        }

        $baseUrl = $this->router->generate('default_index');

        $url = rtrim($baseUrl, '/').$path;

        $response = new RedirectResponse($url);
        $event->setResponse($response);
    }
}

