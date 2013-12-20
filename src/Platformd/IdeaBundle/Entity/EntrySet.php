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
 * @ORM\Entity
 *
 */
class EntrySet implements LinkableInterface {

    const TYPE_SESSION  = 'session';
    const TYPE_IDEA     = 'idea';
    const TYPE_THREAD   = 'thread';
    const TYPE_COMMENT  = 'comment';

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
     * @ORM\ManyToOne(targetEntity="Platformd\IdeaBundle\Entity\EntrySetRegistry", cascade={"persist"})
     */
    protected $entrySetRegistration;

    /**
     * @ORM\Column(type="string", length=255, nullable="true")
     */
    protected $type;

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