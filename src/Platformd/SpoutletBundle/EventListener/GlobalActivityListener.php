<?php

namespace Platformd\SpoutletBundle\EventListener;

use Platformd\GroupBundle\Entity\GroupDiscussion;
use Platformd\GroupBundle\Entity\GroupDiscussionPost;
use Platformd\GroupBundle\Event\GroupEvent;
use Platformd\GroupBundle\Event\GroupDiscussionEvent;
use Platformd\GroupBundle\Event\GroupDiscussionPostEvent;
use Platformd\SpoutletBundle\Entity\Superclass\Activity;
use Platformd\SpoutletBundle\Entity\GlobalActivity;
use Platformd\SpoutletBundle\Entity\GlobalActivityRepository;
use Platformd\GroupBundle\Entity\Group;
use Platformd\SpoutletBundle\Model\ActivityManager;
use Platformd\SpoutletBundle\Event\ContentReportEvent;

use Symfony\Component\EventDispatcher\Event;

class GlobalActivityListener
{
    /**
     * @var ActivityManager
     */
    private $activityManager;

    /**
     * Constructor
     *
     * @param \Platformd\SpoutletBundle\Model\ActivityManager $activityManager
     */
    public function __construct(
        ActivityManager $activityManager
    )
    {
        $this->activityManager = $activityManager;
    }

    /**
     * Log when a user joins a group
     *
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onGroupJoin(Event $event)
    {
        if ($event instanceof GroupEvent) {
            /** @var GroupEvent $event */

            /** @var Group $group */
            $group = $event->getGroup();
            $user = $event->getUser();

            $activity = new GlobalActivity(Activity::SUBJECT_USER, Activity::VERB_JOINED, Activity::OBJECT_GROUP);
            $activity
                ->setSubjectId($user->getId())
                ->setObjectId($group->getId())
                ->setIPAddress(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null);
            ;

            $this->activityManager->save($activity);
        }
    }

    /**
     * Log when a user leaves a group
     *
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onGroupLeave(Event $event)
    {
        if ($event instanceof GroupEvent) {
            /** @var GroupEvent $event */

            /** @var Group $group */
            $group = $event->getGroup();
            $user = $event->getUser();

            $activity = new GlobalActivity(Activity::SUBJECT_USER, Activity::VERB_LEFT, Activity::OBJECT_GROUP);
            $activity
                ->setSubjectId($user->getId())
                ->setObjectId($group->getId())
                ->setIPAddress(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null);
            ;

            $this->activityManager->save($activity);
        }
    }

    /**
     * Log when a user creates a discussion
     *
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onDiscussionCreate(Event $event)
    {
        if ($event instanceof GroupDiscussionEvent) {
            /** @var GroupDiscussionEvent $event */

            /** @var GroupDiscussion $groupDiscussion */
            $groupDiscussion = $event->getGroupDiscussion();

            $user = $groupDiscussion->getAuthor();
            $activity = new GlobalActivity(Activity::SUBJECT_USER, Activity::VERB_CREATED, Activity::OBJECT_DISCUSSION);
            $activity
                ->setSubjectId($user->getId())
                ->setObjectId($groupDiscussion->getId())
                ->setIPAddress(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null);
            ;

            $this->activityManager->save($activity);
        }
    }

    /**
     * Log when a user views a discussion
     *
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onDiscussionView(Event $event)
    {
        if ($event instanceof GroupDiscussionEvent) {
            /** @var GroupDiscussionEvent $event */

            /** @var GroupDiscussion $groupDiscussion */
            $groupDiscussion = $event->getGroupDiscussion();

            $user = $event->getUser();
            $activity = new GlobalActivity(Activity::SUBJECT_USER, Activity::VERB_VIEWED, Activity::OBJECT_DISCUSSION);
            $activity
                ->setSubjectId($user->getId())
                ->setObjectId($groupDiscussion->getId())
                ->setIPAddress(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null);
            ;

            $this->activityManager->save($activity);
        }
    }

    /**
     * Log when a user deletes a discussion
     *
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onDiscussionDelete(Event $event)
    {
        if ($event instanceof GroupDiscussionEvent) {
            /** @var GroupDiscussionEvent $event */

            /** @var GroupDiscussion $groupDiscussion */
            $groupDiscussion = $event->getGroupDiscussion();
            $user = $event->getUser();

            $activity = new GlobalActivity(Activity::SUBJECT_USER, Activity::VERB_DELETED, Activity::OBJECT_DISCUSSION);
            $activity
                ->setSubjectId($user->getId())
                ->setObjectId($groupDiscussion->getId())
                ->setIPAddress(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null);
            ;

            $this->activityManager->save($activity);
        }
    }

    /**
     * Log when a user reports a discussion
     *
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onReportDiscussion(Event $event)
    {
        if ($event instanceof ContentReportEvent) {
            /** @var ContentReportEvent $event */

            $content = $event->getContent();
            $reporter = $event->getReporter();

            if ($content instanceof GroupDiscussion) {
                /** @var GroupDiscussion $groupDiscussion */
                $groupDiscussion = $content;

                $activity = new GlobalActivity(Activity::SUBJECT_USER, Activity::VERB_CREATED, Activity::OBJECT_DISCUSSION);
                $activity
                    ->setSubjectId($reporter->getId())
                    ->setObjectId($groupDiscussion->getId())
                    ->setIPAddress(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null);
                ;

                $this->activityManager->save($activity);
            }
        }
    }

    /**
     * Log when a user replies to a discussion
     *
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onAddToDiscussion(Event $event)
    {
        if ($event instanceof GroupDiscussionPostEvent) {
            /** @var GroupDiscussionPost $groupDiscussionPost */
            $groupDiscussionPost = $event->getGroupDiscussionPost();
            $user = $groupDiscussionPost->getAuthor();

            $activity = new GlobalActivity(Activity::SUBJECT_USER, Activity::VERB_CREATED, Activity::OBJECT_DISCUSSION_POST);
            $activity
                ->setSubjectId($user->getId())
                ->setObjectId($groupDiscussionPost->getId())
                ->setIPAddress(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null);
            ;

            $this->activityManager->save($activity);
        }
    }
}
