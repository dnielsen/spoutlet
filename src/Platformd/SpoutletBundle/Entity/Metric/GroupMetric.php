<?php

namespace Platformd\SpoutletBundle\Entity\Metric;

use DateTime,
    DateTimeZone
;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

use Platformd\SpoutletBundle\Entity\Group;

/**
 * Platformd\SpoutletBundle\Entity\Metric\GroupMetric
 *
 * @ORM\Table(name="metric_group")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\Metric\GroupMetricRepository")
 */
class GroupMetric
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
     * Group these metrics pertain to
     *
     * @var \Platformd\SpoutletBundle\Entity\Group
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Group")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $group;

    /**
     * Number of new members in this group
     *
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $newMembers = 0;


    /**
     * Number of discussions created in this group
     *
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $newDiscussions = 0;


    /**
     * Number of discussions deleted in this group
     *
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $deletedDiscussions = 0;

    /**
     * @var DateTime $date
     *
     * @ORM\Column(type="datetime")
     */
    protected $date;

    /**
     * @var DateTime $date
     *
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updated;

    public function __construct(Group $group)
    {
        $this->group    = $group;
        $this->date     = new DateTime('today midnight', new DateTimeZone('UTC'));
        $this->updated  = new DateTime();
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
     * @param int $deletedDiscussions
     */
    public function setDeletedDiscussions($deletedDiscussions)
    {
        $this->deletedDiscussions = $deletedDiscussions;
    }

    /**
     * @return int
     */
    public function getDeletedDiscussions()
    {
        return $this->deletedDiscussions;
    }

    /**
     * @param \Platformd\SpoutletBundle\Entity\Group $group
     */
    public function setGroup($group)
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
     * @param int $newDiscussions
     */
    public function setNewDiscussions($newDiscussions)
    {
        $this->newDiscussions = $newDiscussions;
    }

    /**
     * @return int
     */
    public function getNewDiscussions()
    {
        return $this->newDiscussions;
    }

    /**
     * @param int $newMembers
     */
    public function setNewMembers($newMembers)
    {
        $this->newMembers = $newMembers;
    }

    /**
     * @return int
     */
    public function getNewMembers()
    {
        return $this->newMembers;
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

    /**
     * @param \DateTime $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
