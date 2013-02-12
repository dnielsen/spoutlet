<?php

namespace Platformd\GroupBundle\Entity\Metric;

use Platformd\GroupBundle\Entity\GroupDiscussion;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

use DateTime;
use DateTimeZone;

/**
 * Platformd\GroupBundle\Entity\Metric\GroupDiscussionMetric
 *
 * @ORM\Table(name="metric_discussion")
 * @ORM\Entity(repositoryClass="Platformd\GroupBundle\Entity\Metric\GroupDiscussionMetricRepository")
 */
class GroupDiscussionMetric
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
     *
     * @var \Platformd\GroupBundle\Entity\GroupDiscussion
     * @ORM\ManyToOne(targetEntity="Platformd\GroupBundle\Entity\GroupDiscussion")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $groupDiscussion;

    /**
     * Number of replies for this discussion
     *
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $replies = 0;

    /**
     * Number of view for this discussion
     *
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $views = 0;

    /**
     * Number of active users in this dicsussion
     *
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $activeUsers = 0;

    /**
     * @var DateTime $date
     *
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $date;

    /**
     * @var DateTime $date
     *
     * @ORM\Column(type="datetime")
     */
    protected $updated;

    public function __construct(GroupDiscussion $groupDiscussion)
    {
        $this->groupDiscussion  = $groupDiscussion;
        $this->date             = new DateTime('today midnight', new DateTimeZone('UTC'));
        $this->updated          = new DateTime();
    }

    /**
     * @param int $activeUsers
     */
    public function setActiveUsers($activeUsers)
    {
        $this->activeUsers = $activeUsers;
    }

    /**
     * @return int
     */
    public function getActiveUsers()
    {
        return $this->activeUsers;
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
     * @param \Platformd\GroupBundle\Entity\GroupDiscussion $groupDiscussion
     */
    public function setGroupDiscussion($groupDiscussion)
    {
        $this->groupDiscussion = $groupDiscussion;
    }

    /**
     * @return \Platformd\GroupBundle\Entity\GroupDiscussion
     */
    public function getGroupDiscussion()
    {
        return $this->groupDiscussion;
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
     * @param int $replies
     */
    public function setReplies($replies)
    {
        $this->replies = $replies;
    }

    /**
     * @return int
     */
    public function getReplies()
    {
        return $this->replies;
    }

    /**
     * @param int $views
     */
    public function setViews($views)
    {
        $this->views = $views;
    }

    /**
     * @return int
     */
    public function getViews()
    {
        return $this->views;
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
