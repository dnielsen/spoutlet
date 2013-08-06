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
    private $encoderFactory;
    private $userProvider;
    private $apiManager;
    private $cevoPasswordHandler;

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
        $currentUser = $token->getUser();

        if ($currentUser instanceof UserInterface) {
            if ($sessionUuid = $this->tryApiAuthentication($currentUser, $presentedPassword) == false) {
                throw new BadCredentialsException('The credentials were changed from another session.');
            }
        } else {
            if ("" === ($presentedPassword = $token->getCredentials())) {
                throw new BadCredentialsException('The presented password cannot be empty.');
            }

            if ($user->getApiSuccessfulLogin()) {
                if (false === $sessionUuid = $this->apiManager->authenticate($user, $presentedPassword)) {
                    throw new BadCredentialsException('The presented password is invalid.');
                }
            } else {

                // Check to see if we can log in via API
                if ($sessionUuid = $this->tryApiAuthentication($user, $presentedPassword) == false) {
                    // Check to see if we have a CEVO-style password
                    if (!$this->cevoPasswordHandler->authenticate($user, $presentedPassword)) {
                        // Check to see if we have a "Platform D" style password
                        if (!$this->encoderFactory->getEncoder($user)->isPasswordValid($user->getPassword(), $presentedPassword, $user->getSalt())) {
                            throw new BadCredentialsException('The presented password is invalid.');
                        }
                    }

                    // Notify API of updated password or throw exception
                    if ($this->apiManager->updatePassword($user, $presentedPassword) == false) {
                        throw new BadCredentialsException('The presented password is invalid.');
                    }

                    // Auth with API now that password has been updated.
                    if ($sessionUuid = $this->tryApiAuthentication($user, $presentedPassword) == false) {
                        throw new BadCredentialsException('The presented password is invalid.');
                    }
                }
            }
        }

        $user->sessionUuid = $sessionUuid;
    }

    protected function tryApiAuthentication($user, $presentedPassword)
    {
        $sessionUuid = $this->apiManager->authenticate($user, $presentedPassword);

        if ($sessionUuid && !$user->getApiSuccessfulLogin()) {
            $user->setApiSuccessfulLogin(new \DateTime());
            return $sessionUuid;
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
}
