<?php

namespace Platformd\SpoutletBundle\ViewModel;

class comment_data
{
    public $parent;
    public $deleted;
    public $upVoteCount;
    public $downVoteCount;
    public $id;
    public $createdAt;
    public $authorId;
    public $authorUsername;
    public $authorAccountLink;
    public $authorAvatar;
    public $body;
    public $publishedReplyCount;
    public $replies = array();
}
