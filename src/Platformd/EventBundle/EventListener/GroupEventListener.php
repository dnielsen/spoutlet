<?php

namespace Platformd\EventBundle\EventListener;

use Symfony\Bundle\FrameworkBundle\Translation\Translator,
    Symfony\Component\Routing\RouterInterface;

use Platformd\EventBundle\Event\EventEvent,
    Platformd\EventBundle\Entity\GroupEvent,
    Platformd\SpoutletBundle\Model\EmailManager,
    Platformd\SpoutletBundle\Util\SiteUtil
;

class GroupEventListener
{
    private $translator;
    private $siteUtil;
    private $emailManager;
    private $router;

    public function __construct(
        Translator $translator,
        SiteUtil $siteUtil,
        EmailManager $emailManager,
        RouterInterface $router
    )
    {
        $this->translator   = $translator;
        $this->siteUtil     = $siteUtil;
        $this->emailManager = $emailManager;
        $this->router = $router;
    }

    /**
     * We send an email when user's event gets approved
     *
     * @param \Platformd\EventBundle\Event\EventEvent $ev
     */
    public function onApprove(EventEvent $ev)
    {
        /** @var $event GroupEvent */
        $event = $ev->getEvent();

        $name = $event->getName();
        $group = $event->getGroup()->getName();
        $type = ($event->getOnline()) ? 'Online' : 'Location';
        $owner = $event->getUser();

        $emailTo            = $owner->getEmail();
        $emailLocale        = $owner->getLocale() ? : 'en';
        $subject            = $this->translator->trans('platformd.event.email.approved.title', array(), 'messages', $emailLocale);
        $message            = nl2br($this->translator->trans('platformd.event.email.approved.message', array(
            '%eventName%' => $name,
            '%eventUrl%' => $this->router->generate($event->getLinkableRouteName(), $event->getLinkableRouteParameters(), true),
        ), 'messages', $emailLocale));


        $this->emailManager->sendHtmlEmail($emailTo, $subject, $message, "Event Approval Notification", $this->siteUtil->getCurrentSite()->getDefaultLocale());

        if ($event instanceof GroupEvent) {

            foreach ($event->getGroup()->getMembers() as $member) {
                $emailTo            = $member->getEmail();
                $emailLocale        = $member->getLocale() ? : 'en';
                $subject            = $this->translator->trans('platformd.event.email.group_announcement.title', array('%eventName%'   => $name), 'messages', $emailLocale);
                $message            = nl2br($this->translator->trans('platformd.event.email.group_announcement.message', array(
                    '%eventName%'   => $name,
                    '%groupName%'   => $group,
                    '%startDate%'   => $event->getStartsAt()->format('l, M j, Y'),
                    '%startTime%'   => $event->getStartsAt()->format('g:i A'),
                    '%timezone%'    => $event->getDisplayTimezone() ? $event->getTimezoneString() : "",
                    '%location%'    => ($event->getOnline()) ? 'Online' : ($event->getLocation() ? $event->getLocation().', ' : '').$event->getAddress(),
                    '%url%'         => $this->router->generate($event->getLinkableRouteName(), $event->getLinkableRouteParameters(), true),
                ), 'messages', $emailLocale));

                $this->emailManager->sendHtmlEmail($emailTo, $subject, $message, "Group Event Invite", $this->siteUtil->getCurrentSite()->getDefaultLocale());
            }

        }
    }

    /**
     * We send an email to all attendees when event gets canceled
     *
     * @param \Platformd\EventBundle\Event\EventEvent $ev
     */
    public function onCancel(EventEvent $ev)
    {
        /** @var $event GroupEvent */
        $event = $ev->getEvent();

        foreach ($event->getAttendees() as $attendee) {
            $emailTo            = $attendee->getEmail();
            $emailLocale        = $attendee->getLocale() ? : 'en';

            $subject            = $this->translator->trans('platformd.event.email.cancel.title', array('%eventName%' => $event->getName()), 'messages', $emailLocale);
            $message            = $this->translator->trans('platformd.event.email.cancel.message', array('%eventName%' => $event->getName()), 'messages', $emailLocale);

            $this->emailManager->sendEmail($emailTo, $subject, $message, "Event Cancellation Notification", $this->siteUtil->getCurrentSite()->getDefaultLocale());
        }
    }

    /**
     * We send an email to all attendees when event gets activated
     *
     * @param \Platformd\EventBundle\Event\EventEvent $ev
     */
    public function onActivate(EventEvent $ev)
    {
        /** @var $event GroupEvent */
        $event = $ev->getEvent();

        foreach ($event->getAttendees() as $attendee) {
            $emailTo            = $attendee->getEmail();
            $emailLocale        = $attendee->getLocale() ?: 'en';

            $subject            = $this->translator->trans('platformd.event.email.activate.title', array('%eventName%' => $event->getName()), 'messages', $emailLocale);
            $message            = $this->translator->trans('platformd.event.email.activate.message', array('%eventName%' => $event->getName(), '%eventStartAt%' => $event->getStartsAt()), 'messages', $emailLocale);

            $this->emailManager->sendEmail($emailTo, $subject, $message, "Event Activation Notification", $this->siteUtil->getCurrentSite()->getDefaultLocale());
        }
    }

    /**
     * We send an email to the group owner when an event that needs approval is created
     *
     * @param \Platformd\EventBundle\Event\EventEvent $ev
     */
    public function onCreate(EventEvent $ev)
    {
        $event = $ev->getEvent();

        if ($event instanceof GroupEvent) {

            $group          = $event->getGroup();
            $groupOwner     = $group->getOwner();
            $eventOwner     = $event->getUser();

            $emailTo        = $groupOwner->getEmail();
            $emailLocale    = $groupOwner->getLocale() ?: 'en';

            $approvalUrl    = $this->router->generate('group_event_pending_approval', array('groupSlug' => $group->getSlug()), true);

            $cevoLinkLocales = array(
                'ja' => '/japan',
                'zh' => '/china',
                'es' => 'latam'
            );

            $userUrl = sprintf('http://www.alienwarearena.com%s/member/%d', array_key_exists($emailLocale, $cevoLinkLocales) ? $cevoLinkLocales[$emailLocale] : "" , $eventOwner->getCevoUserId());

            $subject  = $this->translator->trans('platformd.event.email.require_approval.title', array(
                '%groupName%' => $group->getName()
            ), 'messages', $emailLocale);

            $message = nl2br($this->translator->trans('platformd.event.email.require_approval.message', array(
                "%eventUrl%" => $this->router->generate($event->getLinkableRouteName(), $event->getLinkableRouteParameters(), true),
                "%eventName%" => $event->getName(),
                "%groupName%" => $group->getName(),
                "%dateRange%" => $event->getDateRangeString(),
                "%username%" => $event->getUser()->getUsername(),
                "%userUrl%" => $userUrl,
                "%approvalUrl%" => $approvalUrl,
            ), 'messages', $emailLocale));

            $this->emailManager->sendHtmlEmail($emailTo, $subject, $message, "Group Event Creation Notification", $this->siteUtil->getCurrentSite()->getDefaultLocale());
        }
    }
}
