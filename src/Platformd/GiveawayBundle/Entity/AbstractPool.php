<?php

namespace Platformd\GiveawayBundle\Entity;

use Doctrine\ORM\Mapping as ORM,
    Doctrine\Common\Collections\ArrayCollection
;

use Symfony\Component\HttpFoundation\File\UploadedFile,
    Symfony\Component\Validator\Constraints as Assert
;

use Platformd\SpoutletBundle\Entity\Country,
    Platformd\GiveawayBundle\Entity\DealPool,
    Platformd\GiveawayBundle\Entity\GiveawayPool
;

use Symfony\Component\Validator\ExecutionContext;

/**
 * A mapped super class that all other pools inherit from
 *
 * @ORM\MappedSuperclass
 * @Assert\Callback(methods={"validateFileExtension"})
 */
abstract class AbstractPool
{
    const POOL_SIZE_QUEUE_THRESHOLD = 1024; //3145728; // Key files over 3MB in size will be passed to queue for processing
    const POOL_FILE_S3_PREFIX = 'key_pool';

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Internally-used only notes field
     *
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Min(0)
     */
    protected $maxKeysPerIp;

    /**
     * Used kind of for batching. If 500, then we say we only have 500, until
     * we hit the lowerLimit, then we pop back up to 500. Eventually, when
     * the true number of keys runs out, the number remaining becomes true
     * and goes down to zero.
     *
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Min(0)
     */
    protected $upperLimit;

    /**
     * @see upperLimit
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Min(0)
     */
    protected $lowerLimit;

    /**
     * Whether this is active or not
     *
     * @var bool
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $isActive = false;

    /**
     * @var \Symfony\Component\HttpFoundation\File\UploadedFile
     * @Assert\File(maxSize="6000000")
     */
    protected $keysfile;

    /**
     * @ORM\OneToOne(targetEntity="Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRuleset", cascade={"persist"})
     */
    protected $ruleset;

    public function __construct()
    {
        $this->regions = new ArrayCollection();
    }

    /**
     * Returns whether or not this pool should be treated as active
     *
     * This goes beyond the normal isActive to check anything else.
     * For example, a GiveawayPool is only active if both the pool and
     * the related Giveaway are active
     *
     * @abstract
     * @return boolean
     */
    abstract public function isTotallyActive();

    public function __toString() {
        return 'Pool => { Id = '.$this->getId().', MaxKeysPerIP = '.$this->getMaxKeysPerIp().', Description = "'.$this->getDescription().'" }';
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param boolean $isActive
     */
    public function setIsActive($isActive)
    {
        $this->isActive = (bool) $isActive;
    }

    /**
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * @param int $lowerLimit
     */
    public function setLowerLimit($lowerLimit)
    {
        $this->lowerLimit = $lowerLimit;
    }

    /**
     * @return int
     */
    public function getLowerLimit()
    {
        return $this->lowerLimit;
    }

    /**
     * The upper and lower limit stuff will only be enforced if both values
     * are present and non-zero
     *
     * @return bool
     */
    public function shouldEnforceUpperAndLower()
    {
        return $this->getLowerLimit() > 0 && $this->getUpperLimit() >0;
    }

    /**
     * @param int $maxKeysPerIp
     */
    public function setMaxKeysPerIp($maxKeysPerIp)
    {
        $this->maxKeysPerIp = $maxKeysPerIp;
    }

    /**
     * @return int
     */
    public function getMaxKeysPerIp()
    {
        return $this->maxKeysPerIp;
    }

    /**
     * @param int $upperLimit
     */
    public function setUpperLimit($upperLimit)
    {
        $this->upperLimit = $upperLimit;
    }

    /**
     * @return int
     */
    public function getUpperLimit()
    {
        return $this->upperLimit;
    }

    /**
     * @param Symfony\Component\HttpFoundation\File\UploadedFile
     */
    public function setKeysfile(UploadedFile $file)
    {
        $this->keysfile = $file;
    }

    /**
     * @return Symfony\Component\HttpFoundation\File\UploadedFile
     */
    public function getKeysfile()
    {

        return $this->keysfile;
    }

    public function getRuleset()
    {
        return $this->ruleset;
    }

    public function setRuleset($ruleset)
    {
        $this->ruleset = $ruleset;
    }

    public function getRegions()
    {
        return $this->regions;
    }

    public function setRegions($regions)
    {
        $this->regions = $regions;
    }

    public function isEnabledForCountry($country)
    {
        $allowed = false;

        if ($country instanceof Country) {
            $country = $country->getCode();
        }

        if ($this instanceof GiveawayPool) {
            $rules      = $this->getRuleset();
            $regions    = $this->getRegions();

            $allRules = $rules ? $rules->getRules() : array();

            if (count($regions) < 1 && count($allRules) < 1) {
                return true;
            }

            $countryCodes = array();

            if (count($regions) > 0) {
                foreach ($regions as $region) {
                    foreach ($region->getCountries() as $regionCountry) {

                        $countryCode = $regionCountry->getCode();
                        $countryCodes[$countryCode] = $countryCode;
                    }
                }
            }

            if (count($allRules) > 0) {

                foreach ($allRules as $rule) {

                    $ruleCountry = $rule->getCountry()->getCode();

                    if ($rule->getRuleType() != 'allow') {
                        unset($countryCodes[$ruleCountry]);
                    } else {
                        if (!isset($countryCodes[$ruleCountry])) {
                            $countryCodes[$ruleCountry] = $ruleCountry;
                        }
                    }
                }
            }

            $allowed = isset($countryCodes[$country]);

            return $allowed;
        } else {
            foreach ($this->getAllowedCountries() as $allowedCountry) {
                if ($allowedCountry->getCode() == $country) {
                    return true;
                }
            }

            return $allowed;
        }
    }

    public function validateFileExtension(ExecutionContext $executionContext)
    {
        // error if invalid or no category is specified

        $upload = $this->getKeysfile();

        if ($upload === null) {
            return;
        }

        $originalFileName = $upload->getClientOriginalName();
        $extension        = strtolower(substr(strrchr($originalFileName,'.'),1));

        if ($extension == 'csv') {
            return;
        }

        $propertyPath = $executionContext->getPropertyPath() . '.keysfile';
        $executionContext->setPropertyPath($propertyPath);

        $executionContext->addViolation(
            "The file must be a CSV.",
            array(),
            "keysfile"
        );
    }
}
