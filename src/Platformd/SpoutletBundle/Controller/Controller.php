<?php

namespace Platformd\SpoutletBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Our custom base controller
 */
class Controller extends BaseController
{
    /**
     * @return string
     */
    protected function getLocale()
    {
        return $this->container->get('session')->getLocale();
    }

    /**
     * @return \Platformd\GiveawayBundle\Entity\GiveawayRepository
     */
    protected function getGiveawayRepo()
    {
        return $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('GiveawayBundle:Giveaway');
    }

    protected function setFlash($key, $message)
    {
        return $this->container->get('session')->setFlash($key, $message);
    }

    /**
     * @return \Platformd\UserBundle\Entity\User
     */
    protected function getUser()
    {
        return $this->container->get('security.context')
            ->getToken()
            ->getUser();
    }

    /**
     * Shortcut for enforcing security
     *
     * @param array $roles
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    protected function basicSecurityCheck(array $roles)
    {
        if (!$this->container->get('security.context')->isGranted($roles)) {
            throw new AccessDeniedException();
        }
    }
}