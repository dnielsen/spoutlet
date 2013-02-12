<?php

namespace Platformd\EventBundle\Entity;

use Doctrine\ORM\Mapping as ORM,
    Doctrine\Common\Collections\ArrayCollection,
    Doctrine\Common\Collections\Collection
;

use Vich\GeographicalBundle\Annotation as Vich;

use Platformd\SpoutletBundle\Entity\Site,
    Platformd\EventBundle\Validator\GlobalEventUniqueSlug as AssertUniqueSlug,
    Platformd\UserBundle\Entity\User
;

/**
 * Platformd\EventBundle\Entity\GlobalEvent
 *
 * @ORM\Table(name="global_event")
 * @ORM\Entity
 * @AssertUniqueSlug()
 * @Vich\Geographical(on="update")
 */
class GlobalEvent extends Event
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
     * @ORM\OneToMany(targetEntity="Platformd\EventBundle\Entity\GlobalEventTranslation", mappedBy="translatable", cascade={"all"})
     */
    protected $translations;

    /**
     * Sites this event belongs to - override default sites defined in group if set
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @ORM\JoinTable(name="global_events_sites")
     */
    protected $sites;

    /**
     * Event attendees
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinTable(name="global_events_attendees")
     */
    protected $attendees;

    /**
     * @var string hosted_by
     *
     * @ORM\Column(name="hosted_by", type="string", length=255, nullable=true)
     */
    protected $hosted_by;

    /**
     * @var Site
     */
    protected $currentLocale;

    protected $defaultLocale = 'en';

    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->sites        = new ArrayCollection();

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
     * If there is a set URL that should be used without doing anything else, return it here
     *
     * @return string
     */
    function getLinkableOverrideUrl()
    {
        return false;
    }

    /**
     * Returns the name of the route used to link to this object
     *
     * @return string
     */
    function getLinkableRouteName()
    {
        return 'global_event_view';
    }

    /**
     * Returns an array route parameters to link to this object
     *
     * @return array
     */
    function getLinkableRouteParameters()
    {
        return array(
            'slug' => $this->getSlug()
        );
    }

    /**
     * @param string $hosted_by
     */
    public function setHostedBy($hosted_by)
    {
        $this->hosted_by = $hosted_by;
    }

    /**
     * @return string
     */
    public function getHostedBy()
    {
        return $this->hosted_by;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getAttendees()
    {
        return $this->attendees;
    }

    public function addAttendee(User $attendee)
    {
        $this->attendees->add($attendee);
    }

    public function removeAttendee(User $attendee)
    {
        $this->attendees->removeElement($attendee);
    }

    /**
* @param \Doctrine\Common\Collections\ArrayCollection $attendees
*/
    public function setAttendees($attendees)
    {
        foreach ($attendees as $attendee) {
            $this->addAttendee($attendee);
        }
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getSites()
    {
        return $this->sites;
    }

    public function addSite(Site $site)
    {
        $this->sites->add($site);
    }

    /**
     * @param \Doctrine\Common\Collections\ArrayCollection $sites
     */
    public function setSites(Collection $sites)
    {
        foreach($sites as $site) {
            $this->addSite($site);
        }
    }

    public function removeSite(Site $site)
    {
        $this->sites->removeElement($site);
    }

    public function getTranslations()
    {
        return $this->translations;
    }

    public function addTranslation(EventTranslation $translation)
    {
        $this->translations->add($translation);
        $translation->setTranslatable($this);
    }

    public function setTranslations(Collection $translations)
    {
        foreach ($translations as $translation) {
            $this->addTranslation($translation);
        }
    }

    public function removeTranslation(EventTranslation $translation)
    {
        $this->translations->removeElement($translation);
    }

    /**
     * @param \Platformd\SpoutletBundle\Entity\Site $currentLocale
     */
    public function setCurrentLocale($currentLocale)
    {
        $this->currentLocale = $currentLocale;
    }

    /**
     * @return \Platformd\SpoutletBundle\Entity\Site
     */
    public function getCurrentLocale()
    {
        return $this->currentLocale;
    }
}
