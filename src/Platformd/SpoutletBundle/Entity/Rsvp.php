<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\File;
use Gedmo\Sluggable\Util\Urlizer;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Symfony\Component\Validator\ExecutionContext;

/**
 * @ORM\Entity
 * @ORM\Table(name="rsvp")
 * @Assert\Callback(methods={"validateCodeUpload"})
 **/
class Rsvp implements LinkableInterface
{
    const RSVP_DEFAULT_SUCCESS_MESSAGE = 'Thank you for your RSVP';

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotNull
     */
    protected $name;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotNull
     */
    protected $content;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isCodeRequired = true;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isPublished = false;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"persist"})
     */
    protected $background;

    /**
     * @ORM\Column(name="slug", type="string", length=255)
     */
    protected $slug;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\SpoutletBundle\Entity\RsvpCode", mappedBy="rsvp", cascade={"persist"})
     */
    protected $codes;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\SpoutletBundle\Entity\RsvpAttendee", mappedBy="rsvp")
     */
    protected $attendees;

    /**
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @ORM\JoinTable(name="rsvp_site")
     */
    protected $sites;

    /**
     * @ORM\Column(type="datetime")
     **/
    protected $createdAt;

    /**
     * @ORM\Column(type="string")
     */
    protected $successMessage = self::RSVP_DEFAULT_SUCCESS_MESSAGE;

    public function __construct()
    {
        $this->attendees = new ArrayCollection;
        $this->codes = new ArrayCollection;
        $this->createdAt = new \Datetime;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        if (!$this->getSlug()) {
            $slug = Urlizer::urlize($name);

            $this->setSlug($slug);
        }

        $this->name = $name;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function isCodeRequired()
    {
        return $this->isCodeRequired;
    }

    public function setCodeRequired($isCodeRequired)
    {
        $this->isCodeRequired = $isCodeRequired;
    }

    public function isPublished()
    {
        return $this->isPublished;
    }

    public function setPublished($isPublished)
    {
        $this->isPublished = $isPublished;
    }

    public function getBackground()
    {
        return $this->background;
    }

    public function setBackground($background)
    {
        $this->background = $background;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setSlug($slug)
    {
        # this allows slug to be left blank and set elsewhere without it getting overridden here
        if (!$slug) {
            return;
        }

        $this->slug = $slug;
    }

    public function getCodes()
    {
        return $this->codes;
    }

    public function addCodes($codes)
    {
        foreach ($codes as $code) {
            $this->addCode($code);
        }
    }

    public function addCode(RsvpCode $code)
    {
        $this->codes->add($code);
        $code->setRsvp($this);
    }

    public function getAttendees()
    {
        return $this->attendees;
    }

    public function setAttendees($attendees)
    {
        $this->attendees = $attendees;
    }

    public function getSites()
    {
        return $this->sites;
    }

    public function setSites($sites)
    {
        $this->sites = $sites;
    }

    public function setCodes($codes)
    {
        if ($codes === false) {
            $this->codes = false;
            return;
        }

        foreach ($codes as $code) {
            $this->addCode($code);
        }
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function getLinkableOverrideUrl()
    {
        return false;
    }

    public function getLinkableRouteName()
    {
        return 'rsvp_attend';
    }

    public function getLinkableRouteParameters()
    {
        return array(
            'slug' => $this->getSlug()
        );
    }

    public function getSuccessMessage()
    {
        return $this->successMessage;
    }

    public function setSuccessMessage($value)
    {
        $value = $value ?: self::RSVP_DEFAULT_SUCCESS_MESSAGE;

        $this->successMessage = $value;
    }

    public function validateCodeUpload(ExecutionContext $executionContext)
    {
        if ($this->getCodes() === false) {
            $oldPath = $executionContext->getPropertyPath();
            $executionContext->setPropertyPath($oldPath.'.codes');

            $executionContext->addViolation(
                "You must upload your codes in CSV format.",
                array(),
                "codes"
            );

            $executionContext->setPropertyPath($oldPath);

            return;
        }
    }
}

