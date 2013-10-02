<?php

namespace Platformd\SpoutletBundle\Util;

use Symfony\Component\Security\Core\SecurityContextInterface;
use Platformd\UserBundle\Entity\User;

class FlashUtil
{
    private $cacheUtil;
    private $varnishUtil;
    private $securityContext;
    private $router;

    public function __construct($cacheUtil, SecurityContextInterface $securityContext, $varnishUtil, $router)
    {
        $this->cacheUtil       = $cacheUtil;
        $this->securityContext = $securityContext;
        $this->varnishUtil     = $varnishUtil;
        $this->router          = $router;
    }

    public function setFlash($key, $message)
    {
        $user = $this->securityContext->getToken() ? $this->securityContext->getToken()->getUser() : null;

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
        $user = $this->securityContext->getToken() ? $this->securityContext->getToken()->getUser() : null;

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
