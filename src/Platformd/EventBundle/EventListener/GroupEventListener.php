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
        $group = $event->getGroup();
        $owner = $event->getUser();

        $fromName = $group->getOwner()->getUsername();

        $emailTo            = $owner->getEmail();
        $emailLocale        = $owner->getLocale() ? : 'en';
        $subject            = $this->translator->trans('platformd.event.email.approved.title', array(), 'messages', $emailLocale);
        $message            = nl2br($this->translator->trans('platformd.event.email.approved.message', array(
            '%eventName%'       => $name,
            '%eventUrl%'        => $this->router->generate($event->getLinkableRouteName(), $event->getLinkableRouteParameters(), true),
            '%organizerName%'   => $fromName,
        ), 'messages', $emailLocale));


        $this->emailManager->sendHtmlEmail($emailTo, $subject, $message, "Event Approval Notification", $this->siteUtil->getCurrentSite()->getDefaultLocale(), $fromName);

        if ($event instanceof GroupEvent) {
            $this->sendGroupAnnouncementEmail($event);
        }
    }

    private function sendGroupAnnouncementEmail($event)
    {
        if ($event instanceof GroupEvent) {

            $name = $event->getName();
            $group = $event->getGroup()->getName();
            $fromName = $event->getUser()->getUsername();

            foreach ($event->getGroup()->getMembers() as $member) {
                $emailTo            = $member->getEmail();
                $emailLocale        = $member->getLocale() ? : 'en';
                $subject            = $this->translator->trans('platformd.event.email.group_announcement.title', array('%eventName%'   => $name), 'messages', $emailLocale);
                $message            = nl2br($this->translator->trans('platformd.event.email.group_announcement.message', array(
                    '%eventName%'       => $name,
                    '%groupName%'       => $group,
                    '%startDate%'       => $event->getStartsAt()->format('l, M j, Y'),
                    '%startTime%'       => $event->getStartsAt()->format('g:i A'),
                    '%timezone%'        => $event->getDisplayTimezone() ? $event->getTimezoneString() : "",
                    '%location%'        => ($event->getOnline()) ? 'Online' : $event->getAddress(),
                    '%url%'             => $this->router->generate($event->getLinkableRouteName(), $event->getLinkableRouteParameters(), true),
                    '%organizerName%'   => $fromName,
                ), 'messages', $emailLocale));

                $this->emailManager->sendHtmlEmail($emailTo, $subject, $message, "Group Event Invite", $this->siteUtil->getCurrentSite()->getDefaultLocale(), $fromName);
            }

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
            if ($event->getApproved()) {
                $this->sendGroupAnnouncementEmail($event);
            } else {
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
                    "%approvalUrl%" => $approvalUrl,
                    "%groupOwnerName%" => $groupOwner->getUsername(),
                    "%organizerName%" => $eventOwner->getUsername(),
                    "%startTime%" => $event->getStartsAt()->format('g:i A'),
                    "%timezone%" => $event->getDisplayTimezone() ? $event->getTimezoneString() : "",
                    "%location%" => ($event->getOnline()) ? 'Online' : $event->getAddress(),
                ), 'messages', $emailLocale));

                $fromName = $event->getUser()->getAdminLevel() ? null : $event->getUser()->getUsername();

                $this->emailManager->sendHtmlEmail($emailTo, $subject, $message, "Group Event Creation Notification", $this->siteUtil->getCurrentSite()->getDefaultLocale(), $fromName);
            }
        }
    }
}
