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

    public function __construct($sessionId, array $roles = array())
    {
        parent::__construct($roles);

        $this->sessionId = $sessionId;

        parent::setAuthenticated(count($roles) > 0);
    }

    /**
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
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