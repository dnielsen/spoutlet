<?php

namespace Platformd\SpoutletBundle\Takeover;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Platformd\SpoutletBundle\Takeover\SiteTakeoverManager;
use Symfony\Component\HttpFoundation\RedirectResponse;

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

        $referer            = $event->getRequest()->headers->get('referer');
        $route              = $event->getRequest()->get('_route');
        $isRouteTakeover    = ($route == 'takeover' || $route == 'takeover_specified');

        if (!$isRouteTakeover && (null === $referer || $this->isRefererExternal($referer))) {
            if ($this->manager->currentTakeoverExists()) {

                $session = $event->getRequest()->getSession();
                $session->set(self::TARGET_PATH_KEY, $this->router->generate($route));

                $takeoverUrl    = $this->router->generate('takeover');
                $response       = new RedirectResponse($takeoverUrl);

                $event->setResponse($response);
            }
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
