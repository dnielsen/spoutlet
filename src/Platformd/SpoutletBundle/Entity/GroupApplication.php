<?php

namespace Platformd\SpoutletBundle\Entity;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Platformd\UserBundle\Entity\User;
use Platformd\SpoutletBundle\Entity\Group;
use Platformd\SpoutletBundle\Entity\Site;

use Doctrine\ORM\Mapping as ORM;

/**
 * Platformd\SpoutletBundle\Entity\GroupApplication
 *
 * @ORM\Table(name="pd_groups_applications")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\GroupApplicationRepository")
 */
class GroupApplication
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
     * @var \DateTime $created
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @var \DateTime $updated
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;

    /**
     * @var \Platformd\UserBundle\Entity\User
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $applicant;

     /**
     * @var \Platformd\SpoutletBundle\Entity\Group
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Group")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $group;

     /**
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     */
    private $site;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @Assert\NotNull
     */
    private $reason;


    public function __construct()
    {
        $this->groupLocales = new ArrayCollection();
        $this->members = new ArrayCollection();
    }

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
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

      /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    public function getApplicant()
    {
        return $this->applicant;
    }

    public function setApplicant(User $value)
    {
        $this->applicant = $value;
    }

    public function getGroup()
    {
        return $this->group;
    }

    public function setGroup(Group $value)
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

    public function getReason()
    {
        return $this->reason;
    }

    public function setReason($value)
    {
        $this->reason = $value;
    }
}
