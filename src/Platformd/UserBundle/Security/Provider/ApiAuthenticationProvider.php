<?php

namespace Platformd\UserBundle\Security\Provider;

use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Provider\UserAuthenticationProvider;

use Platformd\CEVOBundle\Password\CEVOPasswordHandler;

class ApiAuthenticationProvider extends UserAuthenticationProvider
{
    const LOG_MESSAGE_PREFIX = '[ApiAuthProvider] ';

    private $encoderFactory;
    private $userProvider;
    private $apiManager;
    private $cevoPasswordHandler;
    private $logger;

    public function __construct($apiManager, UserProviderInterface $userProvider, UserCheckerInterface $userChecker, $providerKey, EncoderFactoryInterface $encoderFactory, $hideUserNotFoundExceptions = true, $cevoPasswordHandler)
    {
        parent::__construct($userChecker, $providerKey, $hideUserNotFoundExceptions);
        $this->encoderFactory      = $encoderFactory;
        $this->userProvider        = $userProvider;
        $this->apiManager          = $apiManager;
        $this->cevoPasswordHandler = $cevoPasswordHandler;
    }

    /**
     * {@inheritdoc}
     */
    protected function checkAuthentication(UserInterface $user, UsernamePasswordToken $token)
    {
        // This line is here to force fingers-crossed logging to log API authentication for monitoring
        $this->logger->err(self::LOG_MESSAGE_PREFIX.'NOT AN ERROR - New API authentication attempt starting.');

        if ("" === ($presentedPassword = $token->getCredentials())) {
            $this->logger->debug(self::LOG_MESSAGE_PREFIX.'Password empty');
            throw new BadCredentialsException('The presented password cannot be empty.');
        }

        if ($user->getApiSuccessfulLogin()) {
            $this->logger->debug(self::LOG_MESSAGE_PREFIX.'User "'.$user->getUuid().'" has previously authed with API - sending API auth request');

            if (false === $sessionInfo = $this->apiManager->authenticate($user, $presentedPassword)) {
                $this->logger->debug(self::LOG_MESSAGE_PREFIX.'API auth failed for previously API authed user "'.$user->getUuid().'" - stopping auth process');
                throw new BadCredentialsException('The presented password is invalid.');
            }
        } else {

            $this->logger->debug(self::LOG_MESSAGE_PREFIX.'User "'.$user->getUuid().'" has not previously authed with API - sending API auth request');
            // Check to see if we can log in via API
            if ($sessionInfo = $this->tryApiAuthentication($user, $presentedPassword) === false) {
                $this->logger->debug(self::LOG_MESSAGE_PREFIX.'API auth attempt for user "'.$user->getUuid().'" failed - falling back to CEVO check');

                // Check to see if we have a CEVO-style password
                if (!$this->cevoPasswordHandler->authenticate($user, $presentedPassword)) {
                    $this->logger->debug(self::LOG_MESSAGE_PREFIX.'CEVO auth attempt for user "'.$user->getUuid().'" failed - falling back to Symfony2 default check');

                    // Check to see if we have a "Platform D" style password
                    if (!$this->encoderFactory->getEncoder($user)->isPasswordValid($user->getPassword(), $presentedPassword, $user->getSalt())) {
                        $this->logger->debug(self::LOG_MESSAGE_PREFIX.'No successful auth method found for "'.$user->getUuid().'" - stopping auth process');
                        throw new BadCredentialsException('The presented password is invalid.');
                    }
                }

                $this->logger->debug(self::LOG_MESSAGE_PREFIX.'User "'.$user->getUuid().'" successfully authed using old method - updating API password');

                // Notify API of updated password or throw exception
                if ($this->apiManager->updatePassword($user, $presentedPassword) === false) {
                    $this->logger->debug(self::LOG_MESSAGE_PREFIX.'Failed to update API password for user "'.$user->getUuid().'" - stopping auth process');
                    throw new BadCredentialsException('The presented password is invalid.');
                }

                $this->logger->debug(self::LOG_MESSAGE_PREFIX.'API password updated for user "'.$user->getUuid().'" - trying API auth');

                // Auth with API now that password has been updated.
                if ($sessionInfo = $this->tryApiAuthentication($user, $presentedPassword) === false) {
                    $this->logger->debug(self::LOG_MESSAGE_PREFIX.'API authentication failed for user "'.$user->getUuid().'" after updating API password - stopping auth process');
                    throw new BadCredentialsException('The presented password is invalid.');
                }

                $this->logger->debug(self::LOG_MESSAGE_PREFIX.'API authentication success for user "'.$user->getUuid().'" after updating API password');
            }
        }

        $this->logger->debug(self::LOG_MESSAGE_PREFIX.'API session generated for user "'.$user->getUuid().'" - session UUID "'.$sessionInfo['uuid'].'"');
        $user->sessionUuid    = $sessionInfo['uuid'];
        $user->sessionExpires = $sessionInfo['expires'];
    }

    protected function tryApiAuthentication($user, $presentedPassword)
    {
        $sessionInfo = $this->apiManager->authenticate($user, $presentedPassword);

        if ($sessionInfo && !$user->getApiSuccessfulLogin()) {
            $user->setApiSuccessfulLogin(new \DateTime());
            return $sessionInfo;
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function retrieveUser($username, UsernamePasswordToken $token)
    {
        $user = $token->getUser();
        if ($user instanceof UserInterface) {
            return $user;
        }

        try {
            $user = $this->userProvider->loadUserByUsername($username);

            if (!$user instanceof UserInterface) {
                throw new AuthenticationServiceException('The user provider must return a UserInterface object.');
            }

            return $user;
        } catch (UsernameNotFoundException $notFound) {
            throw $notFound;
        } catch (\Exception $repositoryProblem) {
            throw new AuthenticationServiceException($repositoryProblem->getMessage(), $token, 0, $repositoryProblem);
        }
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }
}
