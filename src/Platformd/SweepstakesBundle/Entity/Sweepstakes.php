<?php

namespace Platformd\SweepstakesBundle\Entity;

use Platformd\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection,
    Doctrine\Common\Collections\ArrayCollection;
use Platformd\SpoutletBundle\Entity\AbstractEvent;
use Gedmo\Mapping\Annotation as Gedmo;

use Symfony\Component\Validator\Constraints as Assert;
use DateTime;
use Symfony\Component\Locale\Locale;

/**
 * Platformd\SweepstakesBundle\Entity\Sweepstakes
 *
 * @ORM\Entity(repositoryClass="Platformd\SweepstakesBundle\Entity\SweepstakesRepository")
 */
class Sweepstakes extends AbstractEvent
{
    /**
     * A list of countries that are *not* eligible for this sweeps
     *
     * @var array
     * @ORM\Column(type="array")
     */
    protected $disallowedCountries = array();

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $minimumAgeRequirement = 13;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $officialRules;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $liabilityRelease;

    /**
     * @param \Datetime $birthday
     */
    public function isUserOldEnough(Datetime $birthday = null)
    {
        if ($birthday === null) {
            return false;
        }

        $now = $this->getStartsAt();
        $age = $birthday->diff($now)->format('%y');

        if ($this->getMinimumAgeRequirement() && $age < $this->getMinimumAgeRequirement()) {
            return false;
        }

        return true;
    }

    /**
     * Is this country allowed?
     *
     * @param $country
     * @return bool
     */
    public function isCountryAllowed($country)
    {
        return !in_array(strtoupper($country), $this->getDisallowedCountries());
    }

    /**
     * Returns the list of eligible countries
     *
     * This is here because the item in the admin was made to be "disallowed countries",
     * but what they really wanted on the frontend was "eligible" countries
     * @return array
     */
    public function eligibleCountries()
    {
        $allCountryChoices = Locale::getDisplayCountries(\Locale::getDefault());

        $disallowedCountries = array_flip($this->getDisallowedCountries());

        return array_diff_key($allCountryChoices, $disallowedCountries);
    }

    /**
     * Are we between the start and end date when we accept entries
     */
    public function isCurrentlyOpen()
    {
        $now = time();

        if (!$this->getStartsAt() || !$this->getEndsAt()) {
            return true;
        }

        $start = $this->getStartsAt()->format('U');
        $end = $this->getEndsAt()->format('U');

        if ($now < $start || $now > $end) {
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    public function getDisallowedCountries()
    {
        return $this->disallowedCountries;
    }

    /**
     * @param array $disallowedCountries
     */
    public function setDisallowedCountries($disallowedCountries)
    {
        $this->disallowedCountries = $disallowedCountries;
    }

    /**
     * @return int
     */
    public function getMinimumAgeRequirement()
    {
        return $this->minimumAgeRequirement;
    }

    /**
     * @param int $minimumAgeRequirement
     */
    public function setMinimumAgeRequirement($minimumAgeRequirement)
    {
        $this->minimumAgeRequirement = $minimumAgeRequirement;
    }

    /**
     * @return string
     */
    public function getOfficialRules()
    {
        return $this->officialRules;
    }

    /**
     * @param string $officialRules
     */
    public function setOfficialRules($officialRules)
    {
        $this->officialRules = $officialRules;
    }

    /**
     * @return string
     */
    public function getLiabilityRelease()
    {
        return $this->liabilityRelease;
    }

    /**
     * @param string $liabilityRelease
     */
    public function setLiabilityRelease($liabilityRelease)
    {
        $this->liabilityRelease = $liabilityRelease;
    }
}