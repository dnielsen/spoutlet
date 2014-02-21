<?php
/**
 * Created by PhpStorm.
 * User: gimballock
 * Date: 2/13/14
 * Time: 11:21 AM
 */

namespace Platformd\EventBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Platformd\IdeaBundle\Entity\Tag;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Platformd\EventBundle\Entity\Session
 *
 * @ORM\Table(name="event_session")
 * @ORM\Entity
 */
class EventSession implements LinkableInterface //,EntrySetScopeable
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
     * @var \DateTime $date
     * @ORM\Column(name="date", type="datetime", nullable=true)
     */
    protected $date;

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

    /**
     * @ORM\ManyToMany(targetEntity="Platformd\IdeaBundle\Entity\Tag", inversedBy="sessions")
     * @ORM\JoinTable(name="tag_session",
     *      joinColumns={@ORM\JoinColumn(name="session_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="tag", referencedColumnName="tag")}
     * )
     */
    protected $tags;

    /**
     * @ORM\OneToOne(targetEntity="Platformd\IdeaBundle\Entity\Idea", inversedBy="evtSession")
     */
    protected $source_idea;

    /**
     * Constructor
     */
    public function __construct(GroupEvent $event) {
        $this->event        = $event;
        $this->attendees    = new ArrayCollection();
        $this->tags         = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

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
     * @param mixed $source_idea
     */
    public function setSourceIdea($source_idea)
    {
        $this->source_idea = $source_idea;
    }

    /**
     * @return mixed
     */
    public function getSourceIdea()
    {
        return $this->source_idea;
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

    /**
     * @param \DateTime $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    public function getDateString()
    {
        $date = $this->getDate();
        if ($date){
            return $date->format('M d, Y');
        }
        else {
            return null;
        }
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

    public function addTag(Tag $tag)
    {
        if (!$this->hasTag($tag)){
            $this->tags[] = $tag;
        }
    }

    public function addTags($tags)
    {
        foreach ($tags as $tag)
        {
            $this->addTag($tag);
            $tag->addSession($this);
        }
    }

    /**
     * Remove tag
     *
     * @param \Platformd\IdeaBundle\Entity\Tag $tag
     */
    public function removeTag(Tag $tag)
    {
        if ($this->hasTag($tag)){
            $this->tags->removeElement($tag);
        }
    }

    /**
     * Removes all tags
     */
    public function removeAllTags()
    {
        foreach ($this->tags as $tag)
        {
            $this->removeTag($tag);
            $tag->removeSession($this);
        }
    }

    /**
     * @return mixed
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Get string of tags (to populate twig template for edit page)
     * @return string imploded string of tag names
     */
    public function getImplodedTagString()
    {
        $tagNames = array();
        foreach ($this->tags as $tag){
            $tagNames[] = $tag->getTagName();
        }
        return implode(" ", $tagNames);
    }

    public function hasTag(Tag $tag)
    {
        $tagNames = array();
        foreach ($this->tags as $tag_iter){
            $tagNames[] = $tag_iter->getTagName();
        }
        return in_array($tag->getTagName(), $tagNames);
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
//    public function isMemberOf(User $user)
//    {
//        // TODO: Implement isMemberOf() method.
//    }
//
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
        return "event_session";
    }

    /**
     * Returns an array route parameters to link to this object
     *
     * @return array
     */
    function getLinkableRouteParameters()
    {
        return array(
            'groupSlug' => $this->event->getGroup()->getSlug(),
            'eventId'   => $this->event->getId(),
            'sessionId' => $this->id,
        );
    }
}