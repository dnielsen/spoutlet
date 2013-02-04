<?php

namespace Platformd\EventBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Platformd\EventBundle\Entity\GroupEvent
 *
 * @ORM\Table(name="group_event")
 * @ORM\Entity(repositoryClass="Platformd\EventBundle\Repository\GroupEventRepository")
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
    protected $private;

    /**
     * Groups the event pertains to
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Group")
     * @ORM\JoinTable(name="group_events_groups")
     */
    protected $groups;

    /**
     * Sites the event pertains to
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @ORM\JoinTable(name="group_events_sites")
     */
    protected $sites;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->groups = new ArrayCollection();
        $this->sites  = new ArrayCollection();
        parent::__construct();
    }

    /**
     * @param \Doctrine\Common\Collections\ArrayCollection $groups
     */
    public function setGroups($groups)
    {
        $this->groups = $groups;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getGroups()
    {
        return $this->groups;
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
}
