<?php

namespace Platformd\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Platformd\UserBundle\Entity\RegistrationSourceRepository")
 * @ORM\Table(name="registration_source")
 */
class RegistrationSource
{
    const REGISTRATION_SOURCE_TYPE_GIVEAWAY    = 1;
    const REGISTRATION_SOURCE_TYPE_CONTEST     = 2;
    const REGISTRATION_SOURCE_TYPE_SWEEPSTAKES = 3;
    const REGISTRATION_SOURCE_TYPE_DEAL        = 4;
    const REGISTRATION_SOURCE_TYPE_NEWS        = 5;
    const REGISTRATION_SOURCE_TYPE_HOMEPAGE    = 6;
    const REGISTRATION_SOURCE_TYPE_GROUP       = 7;
    const REGISTRATION_SOURCE_TYPE_ARP         = 8;
    const REGISTRATION_SOURCE_TYPE_FORUMS      = 9;
    const REGISTRATION_SOURCE_TYPE_VIDEOS      = 10;
    const REGISTRATION_SOURCE_TYPE_IMAGES      = 11;
    const REGISTRATION_SOURCE_TYPE_OTHER       = 12;

    static $sourceEntities = array(
        self::REGISTRATION_SOURCE_TYPE_GIVEAWAY    => 'GiveawayBundle:Giveaway',
        self::REGISTRATION_SOURCE_TYPE_CONTEST     => 'SpoutletBundle:Contest',
        self::REGISTRATION_SOURCE_TYPE_SWEEPSTAKES => 'SweepstakesBundle:Sweepstakes',
        self::REGISTRATION_SOURCE_TYPE_DEAL        => 'GiveawayBundle:Deal',
        self::REGISTRATION_SOURCE_TYPE_NEWS        => 'NewsBundle:News',
        self::REGISTRATION_SOURCE_TYPE_HOMEPAGE    => null,
        self::REGISTRATION_SOURCE_TYPE_GROUP       => 'GroupBundle:Group',
        self::REGISTRATION_SOURCE_TYPE_ARP         => null,
        self::REGISTRATION_SOURCE_TYPE_FORUMS      => null,
        self::REGISTRATION_SOURCE_TYPE_VIDEOS      => 'VideoBundle:YoutubeVideo',
        self::REGISTRATION_SOURCE_TYPE_IMAGES      => 'SpoutletBundle:GalleryMedia',
        self::REGISTRATION_SOURCE_TYPE_OTHER       => null,
    );

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="Platformd\UserBundle\Entity\User", cascade={"persist"}, inversedBy="registrationSource")
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Country", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    protected $country;

    /**
     * The type of entity that caused the user to register
     *
     * @ORM\Column(name="source_type",type="integer")
     */
    protected $sourceType;

    /**
     * The id of the entity that caused the user to register - string because some entities use a string as the ID (e.g. comment thread)
     *
     * @ORM\Column(name="source_id",type="string", nullable="true")
     */
    protected $sourceId;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    protected $created;

    public function __construct($user, $sourceType, $sourceId, $country) {
        $this->user       = $user;
        $this->sourceType = $sourceType;
        $this->sourceId   = $sourceId;
        $this->country    = $country;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($value)
    {
        $this->user = $value;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function setCountry($value)
    {
        $this->country = $value;
    }

    public function getSourceType()
    {
        return $this->sourceType;
    }

    public function setSourceType($value)
    {
        $this->sourceType = $value;
    }

    public function getSourceId()
    {
        return $this->sourceId;
    }

    public function setSourceId($value)
    {
        $this->sourceId = $value;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setCreated($value)
    {
        $this->created = $value;
    }
}
