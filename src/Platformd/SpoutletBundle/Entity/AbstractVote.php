<?php

namespace Platformd\SpoutletBundle\Entity;

/*
    Abstract vote entitiy. Extend this base entity when implementing voting on a feature.
    It requires that the developer properly map the properties for each implementation.
*/

class AbstractVote
{
    const VOTE_TYPE_UP = 'up';
    const VOTE_TYPE_DOWN = 'down';

    private static $validVoteTypes = array(
        self::VOTE_TYPE_UP,
        self::VOTE_TYPE_DOWN,
    );

    protected $id;

    protected $user;

    protected $ipAddress;

    protected $voteType;

    protected $votedAt;

    public function getId()
    {
        return $this->id;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;
    }

    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    public function setVoteType($voteType)
    {
        if (!in_array($voteType, self::$validVoteTypes)) {
            throw new \InvalidArgumentException(sprintf('Invalid vote type "%s" given', $voteType));
        }

        $this->voteType = $voteType;
    }

    public function getVoteType()
    {
        return $this->voteType;
    }


    public function setVotedAt($votedAt)
    {
        $this->votedAt = $votedAt;
    }

    public function getVotedAt()
    {
        return $this->votedAt;
    }

    public static function getValidVoteTypes()
    {
        return self::$validVoteTypes;
    }
}

