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
use Platformd\TagBundle\Model\TaggableInterface;

/**
 * Platformd\SweepstakesBundle\Entity\Sweepstakes
 * @ORM\Table(name="pd_sweepstakes", uniqueConstraints={@ORM\UniqueConstraint(name="slug_unique", columns={"slug"})})
 * @ORM\Entity(repositoryClass="Platformd\SweepstakesBundle\Entity\SweepstakesRepository")
 */
class Sweepstakes implements TaggableInterface
{
    const COMMENT_PREFIX  = 'sweepstake-';

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var DateTime $entryBeginsAt
     * @ORM\Column(name="entry_begins_at", type="datetime")
     */
    private $entryBeginsAt;

    /**
     * @var DateTime $entryEndsAt
     * @ORM\Column(name="entry_ends_at", type="datetime")
     */
    private $entryEndsAt;

    /**
     * @var boolean $hidden
     * @ORM\Column(name="hidden", type="boolean")
     */
    private $hidden = false;

    /**
     * @var text $content
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    private $content;

    /**
     * @Assert\File(
     *   maxSize="6000000",
     *   mimeTypes={"image/png", "image/jpeg", "image/jpg", "image/gif"}
     * )
     */
    private $backgroundImage;



    /**
     * A list of countries that *are* eligible for this sweeps
     *
     * @var array
     * @ORM\Column(type="array")
     */
    private $allowedCountries = array();

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    private $minimumAgeRequirement = 13;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    private $officialRules;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="Platformd\SweepstakesBundle\Entity\Entry", mappedBy="sweepstakes")
     */
    private $entries;

    /**
     * @var Platformd\TagBundle\Entity\Tag[]
     *
     */
    private $tags;

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

    /**
     * Used to return the commenting thread id that should be used for this sweepstakes
     */
    public function getThreadId()
    {
        if (!$this->getId()) {
            throw new \LogicException('A sweepstakes needs an id before it can have a comment thread');
        }

        return self::COMMENT_PREFIX.$this->getId();
    }

    public function getTags()
    {
        $this->tags = $this->tags ?: new ArrayCollection();

        return $this->tags;
    }

    public function getTaggableType()
    {
        return 'platformd_sweepstakes';
    }

    public function getTaggableId()
    {
        return $this->getId();
    }
}
