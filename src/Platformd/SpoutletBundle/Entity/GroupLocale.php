<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\SpoutletBundle\Entity\Superclass\JoinedLocale;

/**
 * Effectively a many-to-many join table between Group and locale (which is not a real table)
 *
 * @ORM\Table(
 *      name="pd_groups_locales",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="group_locale",
 *              columns={"group_id", "locale"}
 *          )
 *      }
 * )
 * @ORM\Entity()
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\GroupLocaleRepository")
 */
class GroupLocale extends JoinedLocale
{
    /**
     * @var Group
     *
     * @ORM\ManyToOne(targetEntity="Group", inversedBy="groupLocales")
     * @ORM\JoinColumn(onDelete="CASCADE", name="group_id")
     */
    private $group;

    /**
     * @return \Platformd\SpoutletBundle\Entity\Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param \Platformd\SpoutletBundle\Entity\Group $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }
}
