<?php

namespace Platformd\SpoutletBundle\EventListener;

use Platformd\GroupBundle\Entity\GroupDiscussion;
use Platformd\GroupBundle\Entity\GroupDiscussionPost;
use Platformd\GroupBundle\Event\GroupDiscussionPostEvent;
use Platformd\SpoutletBundle\Event\ContentReportEvent;
use Platformd\GroupBundle\Model\GroupManager;
use Symfony\Component\EventDispatcher\Event;

class GroupDiscussionListener
{
    /**
     * @var GroupManager
     */
    private $groupManager;

    public function __construct(GroupManager $groupManager)
    {
        $this->groupManager = $groupManager;
    }

    public function onAddToDiscussion(Event $event)
    {
        if ($event instanceof GroupDiscussionPostEvent) {
            /** @var GroupDiscussionPost $groupDiscussionPost */
            $groupDiscussionPost = $event->getGroupDiscussionPost();

        } elseif ($event instanceof ContentReportEvent) {
            /** @var GroupDiscussionPost $groupDiscussionPost */
            $groupDiscussionPost = $event->getContent();
            if (!$groupDiscussionPost instanceof GroupDiscussionPost) {
                return;
            }
        }

        if (isset($groupDiscussionPost)) {
            $groupDiscussion = $groupDiscussionPost->getGroupDiscussion();
            $groupDiscussion->incReplyCount(1);

            $this->groupManager->saveGroupDiscussion($groupDiscussion);
        }
    }

    public function onRemoveFromDiscussion(Event $event)
    {
        if ($event instanceof GroupDiscussionPostEvent) {
            /** @var GroupDiscussionPost $groupDiscussionPost */
            $groupDiscussionPost = $event->getGroupDiscussionPost();

        } elseif ($event instanceof ContentReportEvent) {
            /** @var GroupDiscussionPost $groupDiscussionPost */
            $groupDiscussionPost = $event->getContent();
            if (!$groupDiscussionPost instanceof GroupDiscussionPost) {
                return;
            }
        };

        if (isset($groupDiscussionPost)) {
            $groupDiscussion = $groupDiscussionPost->getGroupDiscussion();
            $groupDiscussion->incReplyCount(-1);

            $this->groupManager->saveGroupDiscussion($groupDiscussion);
        }
    }
}
