<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Platformd\UserBundle\Entity\User;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Gedmo\Mapping\Annotation as Gedmo;

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
     * @ORM\Column(type="boolean")
     */

    private $deleted = false;

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
