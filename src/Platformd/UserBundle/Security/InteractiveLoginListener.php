<?php

namespace Platformd\UserBundle\Security;

use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use DateTime;
use FOS\UserBundle\Security\InteractiveLoginListener as BaseListener;

class InteractiveLoginListener extends BaseListener
{
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();
        $this->userManager->addLoginRecord($user, $event->getRequest());
        parent::onSecurityInteractiveLogin($event);
    }
}
