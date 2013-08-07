<?php

namespace Platformd\UserBundle\Security\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Security Listener "watches" for the awa_session_key cookie
 * If it sees the cookie, it makes an API call to check if the user can be logged in.
 */
class ApiSessionListener
{
    const COOKIE_NAME = 'awa_session_key';

    protected $securityContext;
    protected $sessionStrategy;
    protected $apiManager;
    protected $apiAuth;
    protected $userManager;
    protected $firewallName;

    public function __construct(SecurityContextInterface $securityContext, SessionAuthenticationStrategyInterface $sessionStrategy, $apiManager, $apiAuth, $userManager, $firewallName)
    {
        $this->securityContext = $securityContext;
        $this->sessionStrategy = $sessionStrategy;
        $this->apiManager      = $apiManager;
        $this->apiAuth         = $apiAuth;
        $this->userManager     = $userManager;
        $this->firewallName    = $firewallName;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        // we don't use API auth
        if (!$this->apiAuth) {
            return;
        }

        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $token = $this->securityContext->getToken();

        // already authenticated
        if ($token && !$token instanceof AnonymousToken && $token->getUser() instanceof UserInterface) {
            return;
        }

        $request = $event->getRequest();
        $sessionUuid = $request->cookies->get(self::COOKIE_NAME);

        if (!$sessionUuid) {
            return;
        }

        if ($sessionUuid) {

            // attempt authentication
            $response = $this->apiManager->getSessionInfo($sessionUuid);

            if (!$response) {
                return;
            }

            if ($response['metaData']['status'] != 200) {
                return;
            }

            $sessionExpiry = \DateTime::createFromFormat(
                \DateTime::ISO8601,
                $response['data']['expires']
            );

            if ($sessionExpiry < new \DateTime()) {
                return;
            }

            $suspendedUntil     = $response['data']['user']['suspended_until'] ? new \DateTime($response['data']['user']['suspended_until']) : null;
            $currentlySuspended = $suspendedUntil ? ($suspendedUntil > new \DateTime()) : false;

            if ($response['data']['user']['banned'] || $currentlySuspended) {
                return;
            }

            $user = $this->userManager->findByUuidOrCreate($response['data']['user']['uuid']);
            $token = new UsernamePasswordToken($user, null, $this->firewallName, $user->getRoles());

            $this->sessionStrategy->onAuthentication($request, $token);
            $this->securityContext->setToken($token);
        }
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    private function logError($msg)
    {
        if ($this->logger) {
            $this->logger->err($msg);
        }
    }
}
