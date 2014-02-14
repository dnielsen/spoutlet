<?php
/**
 * Created by PhpStorm.
 * User: gimballock
 * Date: 2/13/14
 * Time: 11:21 AM
 */

namespace Platformd\EventBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Platformd\EventBundle\Entity\Session
 *
 * @ORM\Table(name="event_session")
 * @ORM\Entity
 */
class EventSession //implements EntrySetScopeable
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
     * Sessions's name
     *
     * @var string $name
     * @Assert\NotBlank(message="Name Required")
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * Session description
     *
     * @var string $content
     * @Assert\NotBlank(message="Content Required")
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    protected $description;

    /**
     * Event the session pertains to
     *
     * @var GroupEvent
     * @ORM\ManyToOne(targetEntity="Platformd\EventBundle\Entity\GroupEvent", inversedBy="sessions")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $event;

    /**
     * Event starts at
     *
     * @var \DateTime $startsAt
     * @ORM\Column(name="starts_at", type="datetime", nullable=true)
     */
    protected $startsAt;

    /**
     * Events ends at
     *
     * @var \DateTime $endsAt
     * @ORM\Column(name="ends_at", type="datetime", nullable=true)
     */
    protected $endsAt;

    /**
     * Session attendees
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinTable
     * @ORM\OrderBy({"username" = "ASC"})
     */
    protected $attendees;

//    /**
//     * @ORM\OneToOne(targetEntity="Platformd\IdeaBundle\Entity\EntrySetRegistry", cascade={"persist"})
//     */
//    protected $entrySetRegistration;

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

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param \Platformd\EventBundle\Entity\GroupEvent $event
     */
    public function setEvent($event)
    {
        $this->event = $event;
    }

    /**
     * @return \Platformd\EventBundle\Entity\GroupEvent
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function getTimeRangeString()
    {
        if ($this->getStartsAt() && $this->getEndsAt()) {
            $startsAtTime = $this->getStartsAt()->format('g:i a');
            $endsAtTime = $this->getEndsAt()->format('g:i a');

            if ($startsAtTime == $endsAtTime) {
                return $startsAtTime;
            } else {
                return $startsAtTime . ' - ' . $endsAtTime;
            }
        } else {
            return null;
        }
    }

    /**
     * @return mixed
     */
    public function getStartsAt()
    {
        return $this->startsAt;
    }

    /**
     * @param mixed $startsAt
     */
    public function setStartsAt($startsAt)
    {
        $this->startsAt = $startsAt;
    }

    /**
     * @return mixed
     */
    public function getEndsAt()
    {
        return $this->endsAt;
    }

    /**
     * @param mixed $endsAt
     */
    public function setEndsAt($endsAt)
    {
        $this->endsAt = $endsAt;
    }

//    public function createEntrySetRegistration()
//    {
//        // TODO: Implement createEntrySetRegistration() method.
//    }
//
//    public function getEntrySetRegistration()
//    {
//        // TODO: Implement getEntrySetRegistration() method.
//    }
//
//    public function getEntrySets()
//    {
//        // TODO: Implement getEntrySets() method.
//    }
//
//    public function getId()
//    {
//        // TODO: Implement getId() method.
//    }
//
//    public function isMemberOf(User $user)
//    {
//        // TODO: Implement isMemberOf() method.
//    }
//
//    /**
//     * If there is a set URL that should be used without doing anything else, return it here
//     *
//     * @return string
//     */
//    function getLinkableOverrideUrl()
//    {
//        // TODO: Implement getLinkableOverrideUrl() method.
//    }
//
//    /**
//     * Returns the name of the route used to link to this object
//     *
//     * @return string
//     */
//    function getLinkableRouteName()
//    {
//        // TODO: Implement getLinkableRouteName() method.
//    }
//
//    /**
//     * Returns an array route parameters to link to this object
//     *
//     * @return array
//     */
//    function getLinkableRouteParameters()
//    {
//        // TODO: Implement getLinkableRouteParameters() method.
//    }
}