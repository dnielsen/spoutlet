<?php

namespace Platformd\EventBundle\Entity;

use Doctrine\ORM\Mapping as ORM,
    Doctrine\Common\Collections\ArrayCollection
;

use Vich\GeographicalBundle\Annotation as Vich;

use Platformd\SpoutletBundle\Entity\Group,
    Platformd\SpoutletBundle\Entity\Site,
    Platformd\EventBundle\Validator\GroupEventUniqueSlug as AssertUniqueSlug
;

/**
 * Platformd\EventBundle\Entity\GroupEvent
 *
 * @ORM\Table(name="group_event")
 * @ORM\Entity
 * @AssertUniqueSlug()
 * @Vich\Geographical(on="update")
 */
class GroupEvent extends Event
{
    /**
     * @var integer $id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Whether event is private or public
     * Private events will not display on listing pages, public will
     *
     * @var boolean $private
     * @ORM\Column(name="private", type="boolean")
     */
    protected $private = false;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\EventBundle\Entity\GroupEventTranslation", mappedBy="translatable", cascade={"all"})
     */
    protected $translations;

    /**
     * @var Site
     */
    protected $currentLocale;

    protected $defaultLocale = 'en';

    /**
     * Groups the event pertains to
     *
     * @var Group
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Group")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $group;

    /**
     * Sites this event belongs to - override default sites defined in group if set
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @ORM\JoinTable(name="group_events_sites")
     */
    protected $sites;

    /**
     * The complete address like "1021 Washington Drive, San Francisco, CA United States"
     *
     * @var string $address
     * @ORM\Column(name="address", type="string", length=255, nullable=true)
     */
    protected $address;

    /**
     * This gets value from Google Location service
     *
     * @var float
     * @ORM\Column(type="decimal", scale=7, nullable=true)
     */
    protected $latitude;

    /**
     * This gets value from Google Location service
     *
     * @var float
     * @ORM\Column(type="decimal", scale=7, nullable=true)
     */
    protected $longitude;

    /**
     * Event attendees
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinTable(name="group_events_attendees")
     * @ORM\OrderBy({"username"})
     */
    protected $attendees;

    /**
     * Constructor
     */
    public function __construct(Group $group)
    {
        $this->group        = $group;
        $this->translations = new ArrayCollection();
        $this->sites        = new ArrayCollection();

        foreach ($this->getGroup()->getSites() as $site) {
            $this->sites->add($site);
        }

        parent::__construct();
    }

    private function translate(Site $locale = null)
    {
        $currentLocale = $locale ?: $this->getCurrentLocale();

        return $this->translations->filter(function($translation) use($currentLocale) {
            return $translation->getLocale() === $currentLocale;
        })->first();
    }

    public function __toString()
    {
        return $this->getName();
    }

    /**
     * Overrides default to add translation
     *
     * @return string
     */
    public function getName()
    {

        $translation = $this->translate();

        $value = null;
        if ($translation) {
            $value = $translation->getName();
        }

        return $value ?: $this->name;
    }

    /**
     * Overrides default to add translation
     *
     * @return string
     */
    public function getContent()
    {
        $translation = $this->translate();

        $value = null;
        if ($translation) {
            $value = $translation->getContent();
        }

        return $value ?: $this->content;
    }

    /**
     * Overrides default to add translation
     *
     * @return \Platformd\MediaBundle\Entity\Media
     */
    public function getBannerImage()
    {
        $translation = $this->translate();

        $value = null;
        if ($translation) {
            $value = $translation->getBannerImage();
        }

        return $value ?: $this->bannerImage;
    }

    /**
     * @param \Platformd\SpoutletBundle\Entity\Group $group
     */
    public function setGroup(Group $group)
    {
        $this->group = $group;
    }

    /**
     * @return \Platformd\SpoutletBundle\Entity\Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param boolean $private
     */
    public function setPrivate($private)
    {
        $this->private = $private;
    }

    /**
     * @return boolean
     */
    public function getPrivate()
    {
        return $this->private;
    }

    /**
     * @param \Doctrine\Common\Collections\ArrayCollection $sites
     */
    public function setSites($sites)
    {
        $this->sites = $sites;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getSites()
    {
        return $this->sites;
    }

    /**
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @Vich\GeographicalQuery
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param float $latitude
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
    }

    /**
     * @return float
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param float $longitude
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
    }

    /**
     * @return float
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * used to dynamically generate routes within twig files to allow multiple event types to be
     * mixed and displayed together
     * e.g. group_event_edit, group_event_delete
     *
     *  @return string
     */
    public function getRoutePrefix()
    {
        return 'group_event_';

    }

    /**
     * Content type for the purposes of content reporting
     *
     * @return string
     */
    public function getContentType()
    {
        return 'GroupEvent';
    }

    /**
     * @param \Doctrine\Common\Collections\ArrayCollection $attendees
     */
    public function setAttendees($attendees)
    {
        $this->attendees = $attendees;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getAttendees()
    {
        return $this->attendees;
    }

    public function setTranslations($translations)
    {
        foreach ($translations as $translation) {
            $this->addTranslation($translation);
        }
    }

    public function getTranslations()
    {
        return $this->translations;
    }

    public function addTranslation(GroupEventTranslation $translation)
    {
        $this->translations->add($translation);
        $translation->setTranslatable($this);
    }

    public function removeTranslation(GroupEventTranslation $translation)
    {
        $this->translations->removeElement($translation);
    }

    public function getThreadId()
    {
        return 'group-event-'.$this->getId();
    }

    public function getLinkableOverrideUrl()
    {
        return false;
    }

    public function getLinkableRouteName()
    {
        return 'group_event_view';
    }

    public function getLinkableRouteParameters()
    {
        return array(
            'eventSlug' => $this->getSlug(),
            'groupSlug' => $this->getGroup()->getSlug(),
        );
    }

    public function getModifyRouteParameters()
    {
        return array(
            'eventId' => $this->getId(),
            'groupSlug' => $this->getGroup()->getSlug(),
        );
    }

    public function getCurrentLocale()
    {
        return $this->currentLocale ?: $this->defaultLocale;
    }

    public function setCurrentLocale(Site $locale = null)
    {
        $this->currentLocale = $locale;
    }
}
