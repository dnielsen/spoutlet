<?php

namespace Platformd\CEVOBundle\Security\CEVO;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Platformd\CEVOBundle\Security\CEVO\CEVOToken;
use Platformd\CEVOBundle\Api\ApiManager;
use Platformd\UserBundle\Entity\UserManager;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Platformd\CEVOBundle\Api\ApiException;
use DateTime;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

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
        // this should not happen, but I've seen it while testing
        // this would be where we get a token in our session, but somehow
        // that token is missing the information from the cookie
        if (!$token->getUserId()) {
            throw new UsernameNotFoundException('Problem getting user information.');
        }

        // setup the api manager to use the new session id
        $this->apiManager->setSessionId($token->getSessionId());
        $this->apiManager->setUserId($token->getUserId());

        $userDetails = $this->apiManager->getAuthenticatedUserDetails();

        // CEVO may send back email, they don't as of right now
        $email = isset($userDetails['email']) ? $userDetails['email'] : null;
        $user = $this->findOrCreateUser($userDetails['user_id'], $email, $userDetails);

        // set the last login time
        $user->setLastLogin(new DateTime());
        $this->userManager->updateUser($user);

        $authenticatedToken = new CEVOToken($token->getSessionId(), $token->getUserId(), $user->getRoles());
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
    	
    	// look for user in DB by cevoUserId
        $existingUser = $this->userManager->findUserBy(array(
            'cevoUserId' => $cevoId,
        ));

        // We found CevoUserId 
        //    Make updates if ncessary 
        //    and pass back existingUser data
        if ($existingUser) {	
        	   $this->checkUpdates($existingUser, $allUserData);
               return $existingUser;
        }

        // No Cevo ID was found, so look for user by email
        // if we find an email, update CevoUserID 
        if ($email && $existingUser = $this->userManager->findUserByEmail($email)) {
            $existingUser->setCevoUserId($cevoId);
            $this->userManager->updateUser($existingUser);
            
           $this->checkUpdates($existingUser, $allUserData);
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

                $this->checkUpdates($existingUser, $allUserData);
                return $existingUser;       
            }
        }

        // CEVO is not sending us an email right now, so use username :/
        $usableEmail = $email ? $email : $allUserData['username'];

        $first_name = isset($allUserData['first_name']) ? $allUserData['first_name'] : null;
        $last_name  = isset($allUserData['last_name'])  ? $allUserData['last_name']  : null;
        $country = isset($allUserData['country']) ? $allUserData['country'] : null;

        $birthday = isset($allUserData['dob']) ? new DateTime($allUserData['dob']) : null;

        // right now, this defaults to setting them into whatever the current locale is
        $newUser = $this->userManager->createUser();
        $newUser->setEmail($usableEmail);
        $newUser->setCevoUserId($cevoId);
        $newUser->setUsername($allUserData['username']);
        $newUser->setLastname($allUserData['last_name']);
        $newUser->setFirstname($allUserData['first_name']);
        
        $newUser->setPassword(self::FAKE_PASSWORD);
        $newUser->setCountry($country);
        $newUser->setBirthdate($birthday);
        $this->userManager->updateUser($newUser);

        // todo - use country to set locale instead of automatic?

        return $newUser;
    }
    
    /**
     * Need to see if certain content was updated on CEVO site and if so
     * update the data. 
     * 
     * @param array $existingUser
     * @param array $allUserData
     */
    protected function checkUpdates($existingUser, $allUserData) {
    	
    	$update = false;
    	
       	$first_name = isset($allUserData['first_name']) ? $allUserData['first_name'] : null;
    	$last_name  = isset($allUserData['last_name'])  ? $allUserData['last_name']  : null;
    	$country = isset($allUserData['country']) ? $allUserData['country'] : null;
    	$birthday = isset($allUserData['dob']) ? new DateTime($allUserData['dob']) : null;

    	// Check first name
    	if ($existingUser->getFirstname() != $first_name) {
    		$existingUser->setFirstname($first_name);
    		$update = true;
    	}
    	
    	// Check last name
    	if ($existingUser->getLastname() != $last_name ) {
    		$existingUser->setLastname($last_name);
    		$update = true;
    	}
    	
    	// Check country
    	if ($existingUser->getCountry() != $country ) {
    		$existingUser->setCountry($country);
    		$update = true;
    	}
    	
    	// Check DOB
    	if ($existingUser->getBirthdate() != $birthday ) {
    		$existingUser->setBirthdate($birthday);
    		$update = true;
    	}
    	
    	if ($update) {
    	    $this->userManager->updateUser($existingUser);
    	}
    	
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof CEVOToken;
    }

}