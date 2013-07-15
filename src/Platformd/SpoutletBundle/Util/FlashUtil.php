<?php

namespace Platformd\SpoutletBundle\Util;

use Symfony\Component\Security\Core\SecurityContextInterface;
use Platformd\UserBundle\Entity\User;

class FlashUtil
{
    private $cacheUtil;
    private $securityContext;

    public function __construct($cacheUtil, SecurityContextInterface $securityContext)
    {
        $this->cacheUtil       = $cacheUtil;
        $this->securityContext = $securityContext;
    }

    public function setFlash($key, $message)
    {
        $user = $this->securityContext->getToken()->getUser();

        if (!$user instanceof User) {
            return;
        }

        $cacheKey    = 'FLASH_MESSAGE::'.$user->getId();
        $flash[$key] = $message;

        $this->cacheUtil->addItem($cacheKey, $flash);
    }

    public function getFlash()
    {
        $user = $this->securityContext->getToken()->getUser();

        if (!$user instanceof User) {
            return;
        }

        $key = 'FLASH_MESSAGE::'.$user->getId();

        return $this->cacheUtil->getAndDeleteItem($key);
    }
}
