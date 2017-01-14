<?php

namespace Platformd\SpoutletBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpKernel\Event\GetResponseEvent;

class AccountNotCompleteListener
{
    private $router;
    private $container;

    /**
     * Constructor
     *
     * @param \Platformd\UserBundle\Entity\UserManager $userManager
     */
    public function __construct(UrlGeneratorInterface $router, ContainerInterface $container)
    {
        $this->router = $router;
        $this->container = $container;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $this->container->get('request');
        $routeName = $request->get('_route');
        $token = $this->container->get('security.token_storage')->getToken();
        $user = $this->getCurrentUser();

        if ($user) {
            if ($user->getFacebookId() && !$user->getPassword()) {
                if ($routeName != 'accounts_incomplete' && $routeName != '_main_user_strip' && $routeName != '_wdt' && $routeName != '_security_logout' && $routeName) {
                    $url = $this->router->generate('accounts_incomplete');
                    $response = new RedirectResponse($url);

                    $event->setResponse($response);
                }
            }

            if ($user->getTwitterId() && !$user->getPassword()) {
                if ($routeName != 'accounts_incomplete' && $routeName != '_main_user_strip' && $routeName != '_wdt' && $routeName != '_security_logout' && $routeName) {
                    $url = $this->router->generate('accounts_incomplete');
                    $response = new RedirectResponse($url);

                    $event->setResponse($response);
                }
            }
        }

        return;
    }

    private function getCurrentUser()
    {
        $token = $this->container->get('security.token_storage')->getToken();
        $user = $token === null ? null : $token->getUser();

        if ($user === 'anon.') {
            return null;
        }

        return $user;
    }
}
