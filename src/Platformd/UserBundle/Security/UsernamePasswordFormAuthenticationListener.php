<?php

namespace Platformd\UserBundle\Security;

use Symfony\Component\Security\Http\Firewall\UsernamePasswordFormAuthenticationListener as BaseListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\SecurityContextInterface;

class UsernamePasswordFormAuthenticationListener extends BaseListener
{
    private $csrfProvider;

    protected function attemptAuthentication(Request $request)
    {
        if ($this->options['post_only'] && 'post' !== strtolower($request->getMethod())) {
            if (null !== $this->logger) {
                $this->logger->debug(sprintf('Authentication method not supported: %s.', $request->getMethod()));
            }

            return null;
        }

        if (null !== $this->csrfProvider) {
            $csrfToken = $request->get($this->options['csrf_parameter'], null, true);

            if (false === $this->csrfProvider->isCsrfTokenValid($this->options['intention'], $csrfToken)) {
                throw new InvalidCsrfTokenException('Invalid CSRF token.');
            }
        }

        $username = trim($request->get($this->options['username_parameter'], null, true));
        $password = $request->get($this->options['password_parameter'], null, true);

        $request->getSession()->set(SecurityContextInterface::LAST_USERNAME, $username);

        return $this->authenticationManager->authenticate(new UsernamePasswordToken($username, $password, $this->providerKey));
    }
}
