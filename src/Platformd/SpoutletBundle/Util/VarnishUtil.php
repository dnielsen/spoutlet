<?php

namespace Platformd\SpoutletBundle\Util;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

use Platformd\UserBundle\Entity\User;

class VarnishUtil
{
    private $varnishDetected = false;
    private $ipLookupUtil;
    private $securityContext;
    private $varnishServer;

    private $ipAddress;

    public function __construct($ipLookupUtil, $securityContext, $varnishServer) {
        $this->ipLookupUtil    = $ipLookupUtil;
        $this->securityContext = $securityContext;
        $this->varnishServer   = $varnishServer;
    }

    /**
     *  $bestEffort - set to true if calling from a request-less section, e.g. a console command. This disables the
     *  varnishDetected check and forgoes the exception that is thrown if unable to ban successfully.
     */
    public function banCachedObject($path, $parameters = array(), $bestEffort = false)
    {
        if ($this->varnishDetected || $bestEffort) {

            try {
                $headers = array();

                if (isset($parameters['userId'])) {
                    $headers[] = 'x-ban-user-id: '.$parameters['userId'];
                }

                if (isset($parameters['countryCode'])) {
                    $headers[] = 'x-ban-country-code: '.$parameters['countryCode'];
                }

                $url  = 'http://' . $this->varnishServer. '/' . ltrim($path, '/');
                $curl = curl_init();

                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "BAN");
                curl_setopt($curl, CURLOPT_URL, $url);

                $result = curl_exec($curl);

            } catch (\Exception $e) {
                if (!$bestEffort) {
                    throw new Exception('Could not ban');
                } else {
                    return false;
                }
            }
        }
    }

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

        $this->ipAddress = $event->getRequest()->getClientIp(true);
    }

    public function onKernelResponse(FilterResponseEvent $event) {

        if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        $response = $event->getResponse();

        $userId = 0;

        if ($token = $this->securityContext->getToken()) {
            $user = $token->getUser();

            if ($user instanceof User) {
                $userId = $user->getId();
            }
        }

        $response->headers->set('X-User-ID', $userId);

        if ($this->varnishDetected) {
            $response->headers->set('X-Varnish-Detected', 1);
            return;
        }

        $response->setSharedMaxAge(0);
        $response->setPrivate();
        $response->headers->set('X-Varnish-Not-Detected', 1);
    }
}
