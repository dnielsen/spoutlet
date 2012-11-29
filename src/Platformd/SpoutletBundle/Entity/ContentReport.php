<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Platformd\UserBundle\Entity\User;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Gedmo\Mapping\Annotation as Gedmo;
use Platformd\SpoutletBundle\Entity\Site;

/**
 * Platformd\SpoutletBundle\Entity\ContentReport
 *
 * @ORM\Table(name="pd_content_report")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\ContentReportRepository")
 */
class ContentReport
{

    static private $validReasons = array(
        'inappropriate_content',
        'spam',
        'violates_intellectual_property',
        'individual_harrassing_me',
    );

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $reason
     *
     * @ORM\Column(type="string")
     * @Assert\NotNull
     */
    private $reason;

    /**
     * The person who created this report
     *
     * @var \Platformd\UserBundle\Entity\User
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $reporter;

    /**
     * @var \DateTime $created
     *
     * @ORM\Column(name="reported_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $reportedAt;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @ORM\JoinColumn(nullable=false)
     */
    private $site;

    /**
     * @ORM\Column(type="boolean")
     */

    private $deleted = false;

    /**
     * @ORM\ManyToOne(targetEntity="GroupImage")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */

    protected $groupImage = null;

    /**
     * @ORM\ManyToOne(targetEntity="GroupNews")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */

    protected $groupNews = null;

    /**
     * @ORM\ManyToOne(targetEntity="GroupVideo")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */

    protected $groupVideo = null;

    /**
     * @ORM\ManyToOne(targetEntity="Group")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */

    protected $group = null;

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
     * @return \Platformd\UserBundle\Entity\User
     */
    public function getReporter()
    {
        return $this->reporter;
    }

    /**
     * @param \Platformd\UserBundle\Entity\User $reporter
     */
    public function setReporter(User $reporter)
    {
        $this->reporter = $reporter;
    }

    /**
     * @return \DateTime
     */
    public function getReportedAt()
    {
        return $this->reportedAt;
    }

    /**
     * @param \DateTime $reportedAt
     */
    public function setReportedAt($reportedAt)
    {
        $this->reportedAt = $reportedAt;
    }

    public function getDeleted()
    {
        return $this->deleted;
    }

    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
    }

    public function getGroupImage()
    {
        return $this->groupImage;
    }

    public function setGroupImage($value)
    {
        $this->groupImage = $value;
    }

    public function getGroupNews()
    {
        return $this->groupNews;
    }

    public function setGroupNews($value)
    {
        $this->groupNews = $value;
    }

    public function getGroupVideo()
    {
        return $this->groupVideo;
    }

    public function setGroupVideo($value)
    {
        $this->groupVideo = $value;
    }

    public function getGroup()
    {
        return $this->group;
    }

    public function setGroup($value)
    {
        $this->group = $value;
    }

    public function getSite()
    {
        return $this->site;
    }

    public function setSite($value)
    {
        $this->site = $value;
    }

    public function getValidReasons()
    {
        return self::$validReasons;
    }

    public function setReason($reason)
    {
        if (!in_array($reason, self::$validReasons)) {
            throw new \InvalidArgumentException(sprintf('Invalid report reason "%s" given', $reason));
        }

        $this->reason = $reason;
    }

    public function getReason()
    {
        return $this->reason;
    }
}
