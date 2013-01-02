<?php

namespace Platformd\SpoutletBundle\Entity\Superclass;

use Doctrine\ORM\Mapping as ORM;

use \DateTime;

/**
 * Represents an activity performed on the site
 * Typical logging table
 *
 * @ORM\MappedSuperclass
 */
abstract class Activity
{
    const SUBJECT_USER = 100;
    const SUBJECT_APP = 101;

    const VERB_REGISTERED = 500;
    const VERB_CREATED = 501;
    const VERB_POSTED = 502;
    const VERB_REPLIED = 503;
    const VERB_EDITED = 504;
    const VERB_DELETED = 505;
    const VERB_JOINED = 506;
    const VERB_LEFT = 507;
    const VERB_SUBSCRIBED = 508;
    const VERB_UNSUBSCRIBED = 509;
    const VERB_REQUESTED = 510;
    const VERB_REPORTED = 511;
    const VERB_VIEWED = 512;

    const OBJECT_APP = 800;
    const OBJECT_GROUP = 801;
    const OBJECT_DISCUSSION = 802;
    const OBJECT_DISCUSSION_POST = 803;
    const OBJECT_PROFILE = 804;
    const OBJECT_COMMENT = 805;

    /**
     * @var integer $subject
     *
     * @ORM\Column(type="smallint")
     */
    protected $subject;

    /**
     * @var integer $verb
     *
     * @ORM\Column(type="smallint")
     */
    protected $verb;

    /**
     * @var integer $object
     *
     * @ORM\Column(type="smallint")
     */
    protected $object;

    /**
     * @var DateTime $date
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $date;

    /**
     * @var Array $data
     *
     * @ORM\Column(type="array", nullable=true)
     */
    protected $data;

    /**
     * @var integer $subjectId
     *
     * @ORM\Column(type="bigint", nullable=true)
     */
    protected $subjectId;

    /**
     * @var integer $ojectId
     *
     * @ORM\Column(type="bigint", nullable=true)
     */
    protected $objectId;

    /**
     * @var integer $IPAddress
     *
     * @ORM\Column(name="ip_address", type="string", length=20, nullable=true)
     */
    protected $IPAddress;


    public function __construct($subject, $verb, $object)
    {
        $this->subject = $subject;
        $this->verb = $verb;
        $this->object = $object;
        $this->date = new \DateTime();
    }

    /**
     * @param \DateTime $date
     *
     * @return Activity 
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param int $object
     *
     * @return Activity 
     */
    public function setObject($object)
    {
        $this->object = $object;

        return $this;
    }

    /**
     * @return int
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param int $subject
     *
     * @return Activity 
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return int
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param int $verb
     *
     * @return Activity 
     */
    public function setVerb($verb)
    {
        $this->verb = $verb;

        return $this;
    }

    /**
     * @return int
     */
    public function getVerb()
    {
        return $this->verb;
    }

    /**
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

/**
     * @param string $IPAddress
     *
     * @return Activity 
     */
    public function setIPAddress($IPAddress)
    {
        $this->IPAddress = $IPAddress;

        return $this;
    }

    /**
     * @return string
     */
    public function getIPAddress()
    {
        return $this->IPAddress;
    }

    /**
     * @param int $objectId
     *
     * @return Activity 
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;

        return $this;
    }

    /**
     * @return int
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * @param int $subjectId
     *
     * @return Activity 
     */
    public function setSubjectId($subjectId)
    {
        $this->subjectId = $subjectId;

        return $this;
    }

    /**
     * @return int
     */
    public function getSubjectId()
    {
        return $this->subjectId;
    }
}
