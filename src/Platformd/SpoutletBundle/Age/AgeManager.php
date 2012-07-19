<?php

namespace Platformd\SpoutletBundle\Age;

use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\HttpFoundation\Session;

/**
 * Manager for keeping track of how old the current user is
 */
class AgeManager
{
    private $securityContext;

    private $session;

    public function __construct(SecurityContextInterface $securityContext, Session $session)
    {
        $this->securityContext = $securityContext;
        $this->session = $session;
    }

    /**
     * Has the user already "confirmed" his/her age?
     *
     * @return bool
     */
    public function isUsersAgeVerified()
    {
        return $this->getUsersAge() !== false;
    }

    /**
     * Returns the current user's age, or false if we don't know
     *
     * Right now, this only checks the session (not also the current user's profile information)
     * @return integer|boolean
     */
    public function getUsersAge()
    {
        $birthday = $this->session->get('user_birthday');
        if (!$birthday) {
            return false;
        }

        $birthdayObj = \DateTime::createFromFormat('Y-m-d', $birthday);
        if (!$birthdayObj) {
            return false;
        }

        return $birthdayObj->diff(new \DateTime('now'))->y;
    }

    /**
     * Sets the user's age into the session
     *
     * @param integer $age
     */
    public function setUsersBirthday(\DateTime $birthday)
    {
        $this->session->set('user_birthday', $birthday->format('Y-m-d'));
    }

    /**
     * Really just used for testing
     */
    public function clearUsersBirthday()
    {
        $this->session->remove('user_birthday');
    }
}