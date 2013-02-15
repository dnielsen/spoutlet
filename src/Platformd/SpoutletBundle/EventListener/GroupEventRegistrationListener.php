<?php

namespace Platformd\SpoutletBundle\EventListener;

use Platformd\GroupBundle\Model\GroupManager;

use Platformd\EventBundle\Event\RegistrationEvent as Event,
    Platformd\EventBundle\Entity\GroupEvent
;

class GroupEventRegistrationListener
{
    private $groupManager;

    public function __construct(GroupManager $groupManager)
    {
        $this->groupManager = $groupManager;
    }

    /**
     * Triggered every time a user registers to an event
     *
     * @param \Platformd\EventBundle\Event\RegistrationEvent $event
     */
    public function onRegister(Event $ev)
    {
        /** @var $event GroupEvent */
        $event  = $ev->getEvent();
        $user   = $ev->getUser();

        try {
            if ($event->getGroup()->getIsPublic()) {
                $this->groupManager->autoJoinGroup($event->getGroup(), $user);
            } else {
                $this->groupManager->autoApplyToGroup($event->getGroup(), $user);
            }
        } catch (\Exception $e) {
            // We do nothing
        }

    }
}
