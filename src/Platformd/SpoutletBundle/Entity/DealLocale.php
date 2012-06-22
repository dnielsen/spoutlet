<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\SpoutletBundle\Entity\Superclass\JoinedLocale;

/**
 * Effectively a many-to-many join table between Deal and locale (which is not a real table)
 *
 * @ORM\Table(
 *      name="pd_deal_locale",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="deal_locale",
 *              columns={"deal_id", "locale"}
 *          )
 *      }
 * )
 * @ORM\Entity()
 */
class DealLocale extends JoinedLocale
{
    /**
     * @var Deal
     *
     * @ORM\ManyToOne(targetEntity="Deal", inversedBy="dealLocales")
     * @ORM\JoinColumn(onDelete="CASCADE", name="deal_id")
     */
    private $deal;

    /**
     * @return \Platformd\SpoutletBundle\Entity\Deal
     */
    public function getDeal()
    {
        return $this->deal;
    }

    /**
     * @param \Platformd\SpoutletBundle\Entity\Deal $deal
     */
    public function setDeal($deal)
    {
        $this->deal = $deal;
    }
}