<?php

namespace Platformd\SpoutletBundle\Util;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Platformd\UserBundle\Entity\User;

class FlashUtil
{
    private $cacheUtil;
    private $varnishUtil;
    private $tokenStorage;
    private $router;

    public function __construct($cacheUtil, TokenStorageInterface $tokenStorage, $varnishUtil, $router)
    {
        $this->cacheUtil       = $cacheUtil;
        $this->tokenStorage = $tokenStorage;
        $this->varnishUtil     = $varnishUtil;
        $this->router          = $router;
    }

    public function setFlash($key, $message)
    {
        $user = $this->tokenStorage->getToken() ? $this->tokenStorage->getToken()->getUser() : null;

        if (!$user instanceof User) {
            return;
        }

        $cacheKey    = 'FLASH_MESSAGE::'.$user->getId();
        $flash       = array(
            'type' => $key,
            'message' => $message,
        );

        $this->cacheUtil->addItem($cacheKey, $flash);

        $path = $this->router->generate('_flash_message');

        try {
            $this->varnishUtil->banCachedObject($path, array('userId' => $user->getId()));
        } catch (\Exception $e) {
            throw new \Exception('Could not ban.');
        }
    }

    public function getFlash($andDelete = true)
    {
        $user = $this->tokenStorage->getToken() ? $this->tokenStorage->getToken()->getUser() : null;

        if (!$user instanceof User) {
            return;
        }

        $key = 'FLASH_MESSAGE::'.$user->getId();

        return $andDelete ? $this->cacheUtil->getAndDeleteItem($key) : $this->cacheUtil->getItem($key);
    }

    public function hasFlash()
    {
        return $this->getFlash(false) ? true : false;
    }
}
