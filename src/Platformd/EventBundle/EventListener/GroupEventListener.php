<?php

namespace Platformd\EventBundle\EventListener;

use Symfony\Bundle\FrameworkBundle\Translation\Translator;

use Platformd\EventBundle\Event\EventEvent,
    Platformd\SpoutletBundle\Model\EmailManager,
    Platformd\SpoutletBundle\Util\SiteUtil
;

class GroupEventListener
{
    private $translator;
    private $siteUtil;
    private $emailManager;
    private $senderEmail;
    private $senderName;

    public function __construct(
        Translator $translator,
        SiteUtil $siteUtil,
        EmailManager $emailManager,
        $senderEmail,
        $senderName
    )
    {
        $this->translator   = $translator;
        $this->siteUtil     = $siteUtil;
        $this->emailManager = $emailManager;
        $this->senderEmail  = $senderEmail;
        $this->senderName   = $senderName;
    }

    /**
     * We send an email when user's group gets approved
     *
     * @param \Platformd\EventBundle\Event\EventEvent $ev
     */
    public function onApprove(EventEvent $ev)
    {
        $event = $ev->getEvent();

        $name = $event->getName();
        $group = $event->getGroup()->getName();
        $type = ($event->getOnline()) ? 'Online' : 'Location';
        $owner = $event->getUser();

        $emailTo            = $owner->getEmail();
        $emailLocale        = $owner->getLocale() ? : 'en';
        $subject            = $this->translator->trans('platformd.event.email.approve.title', array(), 'messages', $emailLocale);
        $message            = $this->translator->trans('platformd.event.email.approve.message', array(
            '%eventName%' => $name,
            '%groupName%' => $group,
            '%eventType%' => $type
        ), 'messages', $emailLocale);


        $this->emailManager->sendEmail($emailTo, $subject, $message, "Event Approval Notification", $this->siteUtil->getCurrentSite()->getDefaultLocale(), $this->senderName, $this->senderEmail);
    }
}
