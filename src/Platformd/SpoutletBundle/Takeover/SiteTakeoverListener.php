<?php

namespace Platformd\SpoutletBundle\Takeover;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Platformd\SpoutletBundle\Takeover\SiteTakeoverManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Cookie;

class SiteTakeoverListener
{
    const TARGET_PATH_KEY = '_site_takeover_target';

    private $router;
    private $manager;
    private $baseHost;

    public function __construct(UrlGeneratorInterface $router, SiteTakeoverManager $manager, $baseHost)
    {
        $this->router   = $router;
        $this->manager  = $manager;
        $this->baseHost = $baseHost;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernel::MASTER_REQUEST != $event->getRequestType()) {
            // don't do anything if it's not the master request
            return;
        }

        $request = $event->getRequest();

        $referer            = $request->headers->get('referer');
        $cookies            = $request->cookies;
        $route              = $request->get('_route');
        $pathParts          = explode('/', $request->getPathInfo());

        $hasCookie          = $cookies->has('pd_site_takeover_viewed');
        $isRouteTakeover    = ($route == 'takeover' || $route == 'takeover_specified');
        $isExternalReferer  = $this->isRefererExternal($referer);
        $isAllowedRoute     = $pathParts[1] == 'admin' || $pathParts[1] == 'healthCheck';

        if (!$isExternalReferer || ($isExternalReferer && $hasCookie) || $isRouteTakeover || $isAllowedRoute) {
            return;
        }

        $currentTakeover = $this->manager->currentTakeoverExists();

        if ($currentTakeover) {

            $takeoverUrl    = $this->router->generate('takeover', array('returnUrl' => urlencode($request->getRequestUri())));
            $response       = new RedirectResponse($takeoverUrl);

            $cookieName     = 'pd_site_takeover_viewed';
            $cookieValue    = '1';
            $cookieExpiry   = new \DateTime('+1 day');
            $cookiePath     = '/';
            $cookieHost     = $this->baseHost;

            $cookie = new Cookie($cookieName, $cookieValue, $cookieExpiry, $cookiePath, $cookieHost, false, false);
            $response->headers->setCookie($cookie);

            $event->setResponse($response);
        }

        return;
    }

    public function isRefererExternal($referer)
    {
        $hostParts = explode('.', parse_url($referer, PHP_URL_HOST));

        $host = end($hostParts);
        $host = prev($hostParts).'.'.$host;

        return !($host == $this->baseHost);
    }
}
