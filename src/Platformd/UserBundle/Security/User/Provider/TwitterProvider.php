<?php

namespace Platformd\UserBundle\Security\User\Provider;

use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Session;
use \TwitterOAuth;
use FOS\UserBundle\Entity\UserManager;
use Symfony\Component\Validator\Validator;

class TwitterProvider implements UserProviderInterface
{
    /**
     * @var \Twitter
     */
    protected $twitter_oauth;
    protected $userManager;
    protected $validator;
    protected $session;

    public function __construct(TwitterOAuth $twitter_oauth, UserManager $userManager,Validator $validator, Session $session)
    {
        $this->twitter_oauth = $twitter_oauth;
        $this->userManager = $userManager;
        $this->validator = $validator;
        $this->session = $session;
    }

    public function supportsClass($class)
    {
        return $this->userManager->supportsClass($class);
    }

    public function findUserByTwitterId($twitterId)
    {
        return $this->userManager->findUserBy(array('twitterId' => $twitterId));
    }

    public function findUserByUsername($username)
    {
        return $this->userManager->findUserBy(array('username' => $username));
    }

    public function loadUserByUsername($username)
    {
        $user = $this->findUserByUsername($username);

        $this->twitter_oauth->setOAuthToken( $this->session->get('access_token') , $this->session->get('access_token_secret'));

        try {
             $info = $this->twitter_oauth->get('account/verify_credentials');
        } catch (Exception $e) {
             $info = null;
        }

        if (!empty($info)) {
            if (empty($user)) {
                $user = $this->userManager->createUser();
                $user->setEnabled(true);
                $user->setPassword('');
            }

            $username = $info->screen_name;

            $user->setTwitterId($info->id);
            $user->setUsername($username);
            $user->setEmail('example@example.com');
            $user->setFirstname($info->name);

            $this->userManager->updateUser($user);
        }

        if (empty($user)) {
            throw new UsernameNotFoundException('The user is not authenticated on twitter');
        }

        return $user;
    }

    public function loadUserByTwitterId($twitterId)
    {
        $user = $this->findUserByTwitterId($twitterId);

        $this->twitter_oauth->setOAuthToken( $this->session->get('access_token') , $this->session->get('access_token_secret'));

        try {
             $info = $this->twitter_oauth->get('account/verify_credentials');
        } catch (Exception $e) {
             $info = null;
        }

        if (!empty($info)) {
            if (empty($user)) {
                $user = $this->userManager->createUser();
                $user->setEnabled(true);
                $user->setPassword('');
            }

            $username = $info->screen_name;

            $user->setTwitterId($info->id);
            $user->setUsername($username);
            $user->setEmail('example@example.com');
            $user->setFirstname($info->name);

            $this->userManager->updateUser($user);
        }

        if (empty($user)) {
            throw new UsernameNotFoundException('The user is not authenticated on twitter');
        }

        return $user;
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$this->supportsClass(get_class($user)) || !$user->getTwitterID()) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getTwitterID());
    }

    public function isUserAuthenticated()
    {
        try {
             $info = $this->twitter_oauth->get('account/verify_credentials');
        } catch (Exception $e) {
             return false;
        }

        return true;
    }

    public function getTwitterId()
    {
        try {
             $info = $this->twitter_oauth->get('account/verify_credentials');
             if($info) {
                return $info->id;
             }

        } catch (Exception $e) {
             return '0';
        }

    }
}
