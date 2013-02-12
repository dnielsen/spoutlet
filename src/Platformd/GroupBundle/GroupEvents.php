<?php

namespace Platformd\GroupBundle;

final class GroupEvents
{
    /**
     * The awa.group.join event is thrown each time a user joins a group
     *
     * The event listener receives a Platformd\GroupBundle\Event\GroupEvent
     * instance.
     *
     * @var string
     */
    const GROUP_JOIN = 'awa.group.join';

    /**
     * The awa.group.leave event is thrown each time a user leaves a group
     *
     * The event listener receives a Platformd\GroupBundle\Event\GroupEvent
     * instance.
     *
     * @var string
     */
    const GROUP_LEAVE = 'awa.group.leave';

    /**
     * The awa.discussion.create event is thrown each time a discussion is created
     *
     * The event listener receives a Platformd\GroupBundle\Event\GroupDiscussionEvent
     * instance.
     *
     * @var string
     */
    const DISCUSSION_CREATE = 'awa.discussion.create';

    /**
     * The awa.discussion.update event is thrown each time a discussion is updated
     *
     * The event listener receives a Platformd\GroupBundle\Event\GroupDiscussionEvent
     * instance.
     *
     * @var string
     */
    const DISCUSSION_UPDATE = 'awa.discussion.update';

    /**
     * The awa.discussion.delete event is thrown each time a discussion is deleted
     *
     * The event listener receives a Platformd\GroupBundle\Event\GroupDiscussionEvent
     * instance.
     *
     * @var string
     */
    const DISCUSSION_DELETE = 'awa.discussion.delete';

    /**
     * The awa.discussion.view event is thrown each time a discussion is viewed
     *
     * The event listener receives a Platformd\GroupBundle\Event\GroupDiscussionEvent
     * instance.
     *
     * @var string
     */
    const DISCUSSION_VIEW = 'awa.discussion.view';

    /**
     * The awa.discussionPost.create event is thrown each time a post is added to a discussion
     *
     * The event listener receives a Platformd\GroupBundle\Event\GroupDiscussionPostEvent
     * instance.
     *
     * @var string
     */
    const DISCUSSION_POST_CREATE = 'awa.discussionPost.create';

    /**
     * The awa.discussionPost.update event is thrown each time a discussion post is updated
     *
     * The event listener receives a Platformd\GroupBundle\Event\GroupDiscussionPostEvent
     * instance.
     *
     * @var string
     */
    const DISCUSSION_POST_UPDATE = 'awa.discussionPost.update';

    /**
     * The awa.discussionPost.update event is thrown each time a discussion post is updated
     *
     * The event listener receives a Platformd\GroupBundle\Event\GroupDiscussionPostEvent
     * instance.
     *
     * @var string
     */
    const DISCUSSION_POST_DELETE = 'awa.discussionPost.delete';
}
