<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRule
 *
 * @ORM\Table(name="pd_country_age_restriction_rule")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRuleRepository")
 */
class CountryAgeRestrictionRule
{
    const RULE_TYPE_ALLOW          = 'allow';
    const RULE_TYPE_DISALLOW       = 'disallow';

    private static $validRuleTypes = array(
        self::RULE_TYPE_ALLOW,
        self::RULE_TYPE_DISALLOW,
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
     * @var integer $country
     *
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Country", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $country;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRuleset", inversedBy="rules")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $ruleset;

    /**
     * @var integer $minAge
     *
     * @ORM\Column(name="min_age", type="integer", nullable="true")
     */
    private $minAge;

    /**
     * @var integer $maxAge
     *
     * @ORM\Column(name="max_age", type="integer", nullable="true")
     */
    private $maxAge;

    /**
     * @var string $ruleType
     *
     * @ORM\Column(name="rule_type", type="string", length="50")
     */
    private $ruleType;

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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set country
     *
     * @param integer $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * Get country
     *
     * @return integer
     */
    public function getCountry()
    {
        return $this->country;
    }

    public function setRuleset($ruleset)
    {
        $this->ruleset = $ruleset;
    }

    public function getRuleset()
    {
        return $this->ruleset;
    }

    /**
     * Set minAge
     *
     * @param integer $minAge
     */
    public function setMinAge($minAge)
    {
        $this->minAge = $minAge;
    }

    /**
     * Get minAge
     *
     * @return integer
     */
    public function getMinAge()
    {
        return $this->minAge;
    }

    /**
     * Set maxAge
     *
     * @param integer $maxAge
     */
    public function setMaxAge($maxAge)
    {
        $this->maxAge = $maxAge;
    }

    /**
     * Get maxAge
     *
     * @return integer
     */
    public function getMaxAge()
    {
        return $this->maxAge;
    }

    public function setRuleType($ruleType)
    {
        if (!in_array($ruleType, self::$validRuleTypes)) {
            throw new \InvalidArgumentException(sprintf('Invalid rule type "%s" given', $ruleType));
        }

        $this->ruleType = $ruleType;
    }

    public function getRuleType()
    {
        return $this->ruleType;
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
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    public static function getValidRuleTypes()
    {
        return self::$validRuleTypes;
    }

    public function isAllowed($age, $country)
    {
        if ($country == $this->getCountry() || !$country){

            if (!$age || ($age > $this->getMinAge() && $age < $this->getMaxAge())) {
                return $this->getRuleType();
            }

            return !$this->getRuleType();

        }

        return null;
    }
}
