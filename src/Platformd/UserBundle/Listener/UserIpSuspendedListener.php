<?php

namespace Platformd\UserBundle\Listener;

use Platformd\UserBundle\Exception\UserIpSuspendedException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\EngineInterface;

class UserIpSuspendedListener
{
    private $router;
    private $templating;

    public function __construct(UrlGeneratorInterface $router, EngineInterface $templating)
    {
        $this->router       = $router;
        $this->templating   = $templating;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (!$event->getException() instanceof UserIpSuspendedException) {
            return;
        }

        $request   = $event->getRequest();
        $sweepsReg = $request->get('_route') == 'sweepstakes_show';

        if ($sweepsReg) {
            $slug = $request->get('slug');
            $url  = $this->router->generate('sweepstakes_show', array('slug' => $slug, 'suspended' => 1));
        } else {
            $url = $this->router->generate('fos_user_registration_register', array('suspended' => 1));
        }

        $event->setResponse(new RedirectResponse($url));

        return;
    }
}
