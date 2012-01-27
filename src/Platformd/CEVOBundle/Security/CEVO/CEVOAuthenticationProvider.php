<?php

namespace Platformd\CEVOBundle\Security\CEVO;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Platformd\CEVOBundle\Security\CEVO\CEVOToken;
use Platformd\CEVOBundle\Api\ApiManager;
use Platformd\UserBundle\Entity\UserManager;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * This is notified after a CEVO Token has been set
 *
 * Its job is to take that information, go to the CEVO API, get the information
 * back, fetch a User (or create on if it doesn't exist), and set an authenticated
 * token with the user information on it.
 */
class CEVOAuthenticationProvider implements AuthenticationProviderInterface
{
    const FAKE_PASSWORD = 'AUTO_GEN_PASSWORD_UNUSED';

    /**
     * @var \Platformd\CEVOBundle\Api\ApiManager
     */
    private $apiManager;

    /**
     * @var \Platformd\UserBundle\Entity\UserManager
     */
    private $userManager;

    public function __construct(UserManager $userManager, ApiManager $apiManager)
    {
        $this->apiManager = $apiManager;
        $this->userManager = $userManager;
    }

    /**
     * Authenticates the user by the CEVOToken
     *
     * The CEVOToken will only be set where there is a session cookie. So
     * we should be safe to assume that the user has logged in, failures
     * are failures.
     *
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @throws \Symfony\Component\Security\Core\Exception\BadCredentialsException
     */
    public function authenticate(TokenInterface $token)
    {
        // setup the api manager to use the new session id
        $this->apiManager->setSessionId($token->getSessionId());

        $userDetails = $this->apiManager->getAuthenticatedUserDetails();
        if (null == $userDetails || isset($userDetails['error']) && $userDetails['error']) {
            // for now we return, this can cause problems as it's seen as an "invalid username/password"
            return;
            throw new BadCredentialsException('API error '.$userDetails['error']);
        }

        // CEVO may send back email, they don't as of right now
        $email = isset($userDetails['email']) ? $userDetails['email'] : null;
        $user = $this->findOrCreateUser($userDetails['id'], $email, $userDetails);

        $authenticatedToken = new CEVOToken($token->getSessionId(), $user->getRoles());
        $authenticatedToken->setUser($user);
        $authenticatedToken->setAttributes($token->getAttributes());

        return $authenticatedToken;
    }

    /**
     * @param $cevoId
     * @param $email
     * @param array $allUserData
     * @return \Platformd\UserBundle\Entity\User
     */
    private function findOrCreateUser($cevoId, $email, array $allUserData)
    {
        $existingUser = $this->userManager->findUserBy(array(
            'cevoUserId' => $cevoId,
        ));

        if ($existingUser) {
            return $existingUser;
        }

        if ($email && $existingUser = $this->userManager->findUserByEmail($email)) {
            $existingUser->setCevoUserId($cevoId);
            $this->userManager->updateUser($existingUser);

            return $existingUser;
        }

        // temporary hack - without email, we can't identify users already in our system
        // we don't want to create another account, so we lookup by username
        if (!$email) {
            $existingUser = $this->userManager->findUserBy(array(
                'cevoUserId' => null,
                'username'   => $allUserData['username']
            ));

            if ($existingUser) {
                $existingUser->setCevoUserId($cevoId);
                $this->userManager->updateUser($existingUser);

                return $existingUser;
            }
        }

        // CEVO is not sending us an email right now, so use username :/
        $usableEmail = $email ? $email : $allUserData['username'];

        // right now, this defaults to setting them into whatever the current locale is
        $newUser = $this->userManager->createUser();
        $newUser->setEmail($usableEmail);
        $newUser->setCevoUserId($cevoId);
        $newUser->setUsername($allUserData['username']);
        $newUser->setPassword(self::FAKE_PASSWORD);
        $this->userManager->updateUser($newUser);

        // todo - use country to set locale instead of automatic?

        return $newUser;
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof CEVOToken;
    }

}