<?php

namespace Platformd\SweepstakesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Platformd\UserBundle\Entity\User;
use Platformd\GiveawayBundle\Entity\AbstractCode;
use Platformd\GiveawayBundle\Entity\AbstractPool;

/**
 * @ORM\Table(name="promo_code_contest_winning_code")
 * @ORM\Entity(repositoryClass="Platformd\SweepstakesBundle\Entity\PromoCodeContestCodeRepository")
 */
class PromoCodeContestCode extends AbstractCode
{
    /**
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @ORM\ManyToOne(targetEntity="Platformd\SweepstakesBundle\Entity\Sweepstakes", inversedBy="winningCodes", cascade={"persist", "remove", "merge"})
     */
    protected $contest;

    /**
     * The user assigned to this key
     *
     * @ORM\JoinColumn(name="user", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User", cascade={"persist", "remove", "merge"})
     */
    protected $user;

    // Required to meet definition of AbstractCode
    public function setPool(AbstractPool $value) { return null; }
    public function getPool()       { return null; }

    public function setContest($value)
    {
        $this->contest = $value;
    }

    public function getContest()
    {
        return $this->contest;
    }

    public function assign(User $user, $ipAddress, $site, $country)
    {
        $this->user = $user;
        $this->assignedAt = new \DateTime();
        $this->ipAddress = $ipAddress;
        $this->setAssignedSite($site);
        $this->setCountry($country);
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }
}
