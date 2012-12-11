<?php

namespace Platformd\SpoutletBundle;

final class GroupEvents
{
    /**
     * The awa.discussionPost.create event is thrown each time a post is added to a discussion
     *
     * The event listener receives a Platformd\SpoutletBundle\Event\GroupDiscussionPostEvent
     * instance.
     *
     * @var string
     */
    const DISCUSSION_POST_CREATE = 'awa.discussionPost.create';

    /**
     * The awa.discussionPost.update event is thrown each time a discussion post is updated
     *
     * The event listener receives a Platformd\SpoutletBundle\Event\GroupDiscussionPostEvent
     * instance.
     *
     * @var string
     */
    const DISCUSSION_POST_UPDATE = 'awa.discussionPost.update';

    /**
     * The awa.discussionPost.update event is thrown each time a discussion post is updated
     *
     * The event listener receives a Platformd\SpoutletBundle\Event\GroupDiscussionPostEvent
     * instance.
     *
     * @var string
     */
    const DISCUSSION_POST_DELETE = 'awa.discussionPost.delete';
}
