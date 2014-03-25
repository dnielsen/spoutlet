<?php
/**
 * Created by PhpStorm.
 * User: gimballock
 * Date: 12/3/13
 * Time: 11:17 AM
 */

namespace Platformd\IdeaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\SpoutletBundle\Link\LinkableInterface;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Collection of User Entries
 *
 * @ORM\Table(name="entry_set")
 * @ORM\Entity(repositoryClass="Platformd\IdeaBundle\Entity\EntrySetRepository")
 *
 */
class EntrySet implements LinkableInterface {

    const TYPE_SESSION  = 'session';
    const TYPE_IDEA     = 'idea';
    const TYPE_THREAD   = 'thread';
    const TYPE_TASK     = 'task';

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $name
     * @ORM\Column(type="string", length=255)
     */
    protected $name;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User", inversedBy="entrySets")
     */
    protected $creator;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\IdeaBundle\Entity\EntrySetRegistry", cascade={"persist"})
     */
    protected $entrySetRegistration;

    /**
     * @ORM\Column(type="string", length=255, nullable="true")
     */
    protected $type;

    /**
     * @ORM\Column(type="text", nullable="true")
     */
    protected $description;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\IdeaBundle\Entity\Idea", mappedBy="entrySet", cascade={"persist", "remove"})
     */
    protected $entries;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isVotingActive;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isSubmissionActive;

    /**
     * @ORM\Column(type="string", nullable="true", length=5000)
     */
    protected $allowedVoters;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->entries = new ArrayCollection();
    }

    public function setCreator($creator)
    {
        $this->creator = $creator;
    }

    public function getCreator()
    {
        return $this->creator;
    }

    public function getEntrySetRegistration() {
        return $this->entrySetRegistration;
    }

    public function setEntrySetRegistration(EntrySetRegistry $container) {
        $this->entrySetRegistration = $container;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setIsVotingActive($isVotingActive)
    {
        $this->isVotingActive = $isVotingActive;
        return $this;
    }
    public function getIsVotingActive()
    {
        return $this->isVotingActive;
    }

    public function setIsSubmissionActive($isSubmissionActive)
    {
        $this->isSubmissionActive = $isSubmissionActive;
        return $this;
    }
    public function getIsSubmissionActive()
    {
        return $this->isSubmissionActive;
    }

    public function setAllowedVoters($allowedVoters)
    {
        $this->allowedVoters = $allowedVoters;
    }

    public function getAllowedVoters()
    {
        return $this->allowedVoters;
    }

    public function containsVoter($voter)
    {
        if( strlen($this->allowedVoters) == 0)
            return false;

        $voters = preg_split("/[\s,]+/", trim($this->allowedVoters));
        if (in_array($voter, $voters)) {
            return true;
        }

        return false;
    }

    public function getEntries()
    {
        return $this->entries;
    }

    public function setEntries($entries)
    {
        $this->entries = $entries;
    }

    public function getPopularEntries($limit = 6)
    {
        $entries = $this->entries->toArray();

        usort($entries, function($a, $b)
        {
            $aVotes = $a->getNumVotes();
            $bVotes = $b->getNumVotes();

            if ($aVotes == $bVotes) {
                return 0;
            }
            return ($aVotes < $bVotes) ? +1 : -1;
        });

        $topEntries = array_slice($entries, 0, $limit);

        // filter out any entries with 0 votes
       /* $popularEntries = array_filter($topEntries, function($entry)
        {
            return ($entry->getNumVotes() > 0);
        });*/

        return $topEntries;
    }

    public function getNumEntries(){
        return $this->entries->count();
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * If there is a set URL that should be used without doing anything else, return it here
     *
     * @return string
     */
    public function getLinkableOverrideUrl()
    {
        return false;
    }

    /**
     * Returns the name of the route used to link to this object
     *
     * @return string
     */
    public function getLinkableRouteName()
    {
        return 'entry_set_view';
    }

    /**
     * Returns an array route parameters to link to this object
     *
     * @return array
     */
    public function getLinkableRouteParameters()
    {
        return array(
            'entrySetId' => $this->getId(),
        );
    }


} 