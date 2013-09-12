<?php

namespace Platformd\GroupBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Platformd\SpoutletBundle\Entity\MassEmail;

/**
 * @ORM\Table(name="pd_group_email")
 * @ORM\Entity(repositoryClass="Platformd\GroupBundle\Entity\GroupMassEmailRepository")
 */
class GroupMassEmail extends MassEmail
{
    /**
     * @ORM\ManyToMany(targetEntity="Platformd\UserBundle\Entity\User")
     * @ORM\JoinTable(name="pd_group_email_recipient")
     */
    protected $recipients;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\GroupBundle\Entity\Group")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $group;

    public function __construct($group)
    {
        $this->recipients = new ArrayCollection();
        $this->group = $group;
    }

    public function getGroup()
    {
        return $this->group;
    }

    public function setGroup($value)
    {
        $this->group = $value;
    }

    public function getEmailType()
    {
        return 'Group Mass Email';
    }
}
