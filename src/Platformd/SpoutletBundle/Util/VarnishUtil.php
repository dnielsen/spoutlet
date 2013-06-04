<?php

namespace Platformd\SpoutletBundle\Util;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class VarnishUtil
{
    private $varnishDetected = false;

    public function cacheResponse($response, $sharedMaxAge, $maxAge = 0) {

        if ($this->varnishDetected && $sharedMaxAge > 0) {
            $response->setSharedMaxAge($sharedMaxAge);
            $response->setPublic();
        } else {
            $response->setSharedMaxAge(0);
            $response->setPrivate();
        }

        $response->setMaxAge($maxAge);
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $request = $event->getRequest();

        if ($request === null) {
            return;
        }

        $this->varnishDetected = $request->headers->get('surrogate-capability') === 'abc=ESI/1.0';
    }

    public function onKernelResponse(FilterResponseEvent $event) {

        if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        $response = $event->getResponse();

        if ($this->varnishDetected) {
            $response->headers->set('X-Varnish-Detected', 1);
            return;
        }

        $response->setSharedMaxAge(0);
        $response->setPrivate();
        $response->headers->set('X-Varnish-Not-Detected', 1);
    }
}
