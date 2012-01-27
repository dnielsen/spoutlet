<?php

namespace Platformd\CEVOBundle\Security\CEVO;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

/**
 * This token represents the authentication credentials we suck in from CEVO
 *
 * This is a strange token, because the only thing we really look for is
 * a cookie with the sessionid. This is what we use for the credentials
 */
class CEVOToken extends AbstractToken
{
    private $sessionId;

    private $userId;

    public function __construct($sessionId, $userId, array $roles = array())
    {
        parent::__construct($roles);

        $this->sessionId = $sessionId;
        $this->userId = $userId;

        parent::setAuthenticated(count($roles) > 0);
    }

    /**
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Returns the user credentials.
     *
     * @return mixed The user credentials
     */
    public function getCredentials()
    {
        return $this->getSessionId();
    }

}