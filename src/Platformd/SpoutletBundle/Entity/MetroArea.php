<?php

namespace Platformd\SpoutletBundle\Entity;

use Platformd\SpoutletBundle\Entity\Event;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Platformd\SpoutletBundle\Entity\MetroArea
 *
 * @ORM\Table(name="metro_area")
 * @ORM\Entity
 */
class MetroArea
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $label
     *
     * @ORM\Column(name="label", type="string", length=255)
     */
    private $label;

    /**
     * @var string $slug
     *
     * @ORM\Column(name="slug", type="string", length=255)
     */
    private $slug;

    /**
     * @var Platformd\SpoutletBundle\Entity\Event $events
     *
     * @ORM\OneToMany(targetEntity="Event", mappedBy="events")
     */
    private $events;

    public function __construct()
    {
        $this->events = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set label
     *
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * Get label
     *
     * @return string 
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set slug
     *
     * @param string $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * Get slug
     *
     * @return string 
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set user_id
     *
     * @param Platformd\SpoutletBundle\Entity\Event $events
     */
    public function setEvents(\Platformd\SpoutletBundle\Entity\Event $events)
    {
        $this->events = $events;
    }

    /**
     * Get user
     *
     * @return Platformd\SpoutletBundle\Entity\Event 
     */
    public function getEvents()
    {
        return $this->events;
    }
}