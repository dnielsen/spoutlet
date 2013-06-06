<?php

namespace Platformd\SpoutletBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpFoundation\RequestMatcher;

class PageNotFoundExceptionListener
{
    private $router;
    private $siteUtil;

    static private $urlMap = array(
        '/about'    => '/pages/about',
        '/contact'  => '/pages/contact',
    );

    public function __construct(RouterInterface $router, $siteUtil) {
        $this->router = $router;
        $this->siteUtil = $siteUtil;
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

        if (substr($path, -1, 1) == '/') {
            $path = substr($path, 0, -1);

            try {
                $routeInfo = $this->router->match($path);

                // Prevent redirection loop from Symfony re-adding a trailing slash
                if ($routeInfo['_controller'] != 'Symfony\Bundle\FrameworkBundle\Controller\RedirectController::urlRedirectAction') {
                    $baseUrl = $this->router->generate('default_index');

                    $url = rtrim($baseUrl, '/').$path;

                    $response = new RedirectResponse($url);
                    $event->setResponse($response);
                }

            } catch (ResourceNotFoundException $e) {

            }
        }

        $currentSite = $this->siteUtil->getCurrentSite();

        if (!$currentSite) {
            return;
        }

        $features    = $currentSite->getSiteFeatures();

        if (!$features->getHasForwardOn404()) {
            return;
        }

        $config      = $currentSite->getSiteConfig();

        $forwardBaseUrl = $config->getForwardBaseUrl();
        $forwardedPaths = $config->getForwardedPaths();
        $request        = $event->getRequest();
        $matcher        = new RequestMatcher();

        foreach ($forwardedPaths as $forwardedPath) {
            $matcher->matchPath($forwardedPath);

            if ($matcher->matches($request)) {
                $path = $request->getPathInfo();

                if (isset(self::$urlMap[$path])) {
                    $path = self::$urlMap[$path];
                }

                $absoluteUrl = sprintf('%s%s', $forwardBaseUrl, $path);

                $response = new RedirectResponse($absoluteUrl);
                $event->setResponse($response);
            }
        }

        return;
    }
}
