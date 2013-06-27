<?php

namespace Platformd\UserBundle\Security\User\Provider;

use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use \BaseFacebook;
use \FacebookApiException;

class FacebookProvider implements UserProviderInterface
{
    /**
     * @var \Facebook
     */
    protected $facebook;
    protected $userManager;
    protected $validator;
    protected $container;

    public function __construct(BaseFacebook $facebook, $userManager, $validator, $container)
    {
        $this->facebook     = $facebook;
        $this->userManager  = $userManager;
        $this->validator    = $validator;
        $this->container    = $container;
    }

    public function supportsClass($class)
    {
        return $this->userManager->supportsClass($class);
    }

    public function findUserByFbId($fbId)
    {
        return $this->userManager->findUserBy(array('facebookId' => $fbId));
    }

    public function findUserByUsername($username)
    {
        return $this->userManager->findUserBy(array('username' => $username));
    }

    public function loadUserByUsername($username)
    {
        $user = $this->findUserByUsername($username);

        try {
            $fbdata = $this->facebook->api('/me');
        } catch (FacebookApiException $e) {
            $fbdata = null;
        }

        if (!empty($fbdata)) {
            // TODO use http://developers.facebook.com/docs/api/realtime
            $user->setFBData($fbdata);

            if (count($this->validator->validate($user, 'Facebook'))) {
                // TODO: the user was found obviously, but doesnt match our expectations, do something smart
                throw new UsernameNotFoundException('The facebook user could not be stored');
            }
            $this->userManager->updateUser($user);
        }

        if (empty($user)) {
            throw new UsernameNotFoundException('The user is not authenticated on facebook');
        }

        return $user;
    }

    public function loadUserByFacebookId($facebookId)
    {
        $user = $this->findUserByFbId($facebookId);

        try {
            $fbdata = $this->facebook->api('/me');
        } catch (FacebookApiException $e) {
            $fbdata = null;
        }

        if (!empty($fbdata)) {
            if (empty($user)) {
                $user = $this->userManager->createUser();
                $user->setUsername($fbdata['first_name'].'.'.$fbdata['last_name']);
                $user->setEnabled(true);
                $user->setPassword('');
            }

            // TODO use http://developers.facebook.com/docs/api/realtime
            $user->setFBData($fbdata);

            if (count($this->validator->validate($user, 'Facebook'))) {
                // TODO: the user was found obviously, but doesnt match our expectations, do something smart
                throw new UsernameNotFoundException('The facebook user could not be stored');
            }
            $this->userManager->updateUser($user);
        }

        if (empty($user)) {
            throw new UsernameNotFoundException('The user is not authenticated on facebook');
        }

        return $user;
    }

    public function deauthorize()
    {
        $signedRequest = $this->facebook->getSignedRequest();
        $facebookId    = $signedRequest['user_id'];
        $user          = $this->findUserByFbId($facebookId);

        if($user) {
            $user->setFacebookId('');
            $this->userManager->updateUser($user);
        }
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$this->supportsClass(get_class($user)) || !$user->getFacebookId()) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getFacebookId());
    }

    public function getOauthUrl()
    {
        $request        = $this->container->get('request');
        $scope          = implode(',', $this->container->getParameter('fos_facebook.permissions'));
        $redirectUri    = $this->container->get('router')->generate('_security_check', array(), true);
        $state          = md5(uniqid(rand(), true)); //CSRF protection

        $oauthUrl = $this->facebook->getLoginUrl(
           array(
                'scope'         => $scope,
                'redirect_uri'  => $redirectUri,
                'state'         => $state,
        ));

        return $oauthUrl;
    }

    public function isUserAuthenticated()
    {
        try {
            $me = $this->facebook->api('/me');
            if ($me) {
              return true;
            }
        } catch (FacebookApiException $e) {
            return false;
        }
    }

    public function getFacebookId()
    {
        try {
            $me = $this->facebook->api('/me');
            if ($me) {
              return $me['id'];
            }
        } catch (FacebookApiException $e) {
            return '0';
        }
    }
}
