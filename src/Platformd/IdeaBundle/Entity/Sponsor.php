<?php
/**
 * Created by PhpStorm.
 * User: gimballock
 * Date: 1/28/14
 * Time: 4:35 PM
 */

namespace Platformd\IdeaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass="Platformd\IdeaBundle\Entity\SponsorRepository")
 * @ORM\Table(name="sponsor")
 */
class Sponsor
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * A name for this sponsor
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $name;

    /**
     * Link to sponsor's page
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $url;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     */
    protected $creator;

    /**
     * @ORM\OneToOne(targetEntity="Platformd\GroupBundle\Entity\Group", inversedBy="sponsor", cascade={"persist"})
     */
    protected $department;

    /**
     * @ORM\OneToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"remove", "persist"})
     */
    protected $image;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\IdeaBundle\Entity\SponsorRegistry", mappedBy="sponsor", cascade={"remove", "persist"})
     */
    protected $sponsorRegistrations;


    public function __construct()
    {
        $this->sponsorRegistrations = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setDepartment($department)
    {
        $this->department = $department;
    }

    public function getDepartment()
    {
        return $this->department;
    }

    public function addSponsorRegistration($sponsorRegistration)
    {
        $this->sponsorRegistrations->add($sponsorRegistration);
    }

    public function getSponsorRegistrations()
    {
        return $this->sponsorRegistrations;
    }

    public function createSponsorRegistration()
    {
        $sponsorRegistration = new SponsorRegistry(null, null, $this, null);
        $this->addSponsorRegistration($sponsorRegistration);

        return $sponsorRegistration;
    }

    public function getGroups()
    {
        $sponsorRegistrations = $this->sponsorRegistrations;

        $groups = array();
        foreach ($sponsorRegistrations as $reg) {
            $group = $reg->getGroup();
            if ($group) {
                $groups[] = $group;
            }
        }

        return $groups;
    }

    public function getLevel($scope, $containerId)
    {
        foreach ($this->sponsorRegistrations as $reg) {
            if ($scope == 'group' && $group = $reg->getGroup()) {
                if ($group->getId() == $containerId) {
                    return $reg->getLevel();
                }
            } elseif (($scope == 'event' || $scope == 'global_event') && $event = $reg->getEvent()) {
                if ($event->getId() == $containerId) {
                    return $reg->getLevel();
                }
            }
        }
        return null;
    }

    public function getEvents()
    {
        $sponsorRegistrations = $this->sponsorRegistrations;

        $events = array();
        foreach ($sponsorRegistrations as $reg) {
            $event = $reg->getEvent();
            if ($event) {
                $events[] = $event;
            }
        }

        return $events;
    }

    public function setCreator($creator)
    {
        $this->creator = $creator;
    }

    public function getCreator()
    {
        return $this->creator;
    }

    public function setImage($image)
    {
        $this->image = $image;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function getUrl()
    {
        return $this->url;
    }
}
