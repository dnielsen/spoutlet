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
     * A list of countries that *are* eligible for this sweeps
     *
     * @var array
     * @ORM\Column(type="array")
     */
    protected $allowedCountries = array();

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
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="Platformd\SweepstakesBundle\Entity\Entry", mappedBy="sweepstakes")
     */
    protected $entries;

    public function __construct()
    {
        $this->entries = new ArrayCollection();
    }

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
        return in_array(strtoupper($country), $this->getAllowedCountries());
    }

    /**
     * Are we between the start and end date when we accept entries
     */
    public function isCurrentlyOpen()
    {
        $now = time();

        if (!$this->getStartsAt()) {
            return false;
        }

        $start = $this->getStartsAt()->format('U');
        // either use the end date, or fake it way into the future
        // if ends date is blank, the fun never ends!
        $end = $this->getEndsAt() ? $this->getEndsAt()->format('U') : time() + 1000000000;

        if ($now < $start || $now > $end) {
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    public function getAllowedCountries()
    {
        return $this->allowedCountries;
    }

    /**
     * Attempts to translate the country codes into an array of country names
     *
     * In a more perfect world, this logic is moved elsewhere
     *
     * @return array
     */
    public function getAllowedCountryNames()
    {
        $names = array();
        $allCountryChoices = Locale::getDisplayCountries(\Locale::getDefault());
        foreach ($this->getAllowedCountries() as $countryCode) {
            $name = isset($allCountryChoices[$countryCode]) ? $allCountryChoices[$countryCode] : $countryCode;

            $names[] = $name;
        }

        return $names;
    }

    /**
     * @param array $allowedCountries
     */
    public function setAllowedCountries($allowedCountries)
    {
        $this->allowedCountries = $allowedCountries;
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
     * Returns the route name to this item's show page
     *
     * @deprecated Use LinkableInterface
     * @return string
     */
    public function getShowRouteName()
    {
        return 'sweepstakes_show';
    }

    /**
     * @return int
     */
    public function getEntriesCount()
    {
        return count($this->entries);
    }

    /**
     * Returns the name of the route used to link to this object
     *
     * @deprecated Use the LinkableInterface
     * @return string
     */
    public function getLinkableRouteName()
    {
        return 'sweepstakes_show';
    }
}
