<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRuleset
 *
 * @ORM\Table(name="pd_country_age_restriction_ruleset")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRulesetRepository")
 */
class CountryAgeRestrictionRuleset
{
    const PARENT_TYPE_DEAL          = 'deal';
    const PARENT_TYPE_SWEEPSTAKE    = 'sweepstake';
    const PARENT_TYPE_CONTEST       = 'contest';

    private static $validParentTypes = array(
        self::PARENT_TYPE_DEAL,
        self::PARENT_TYPE_SWEEPSTAKE,
        self::PARENT_TYPE_CONTEST,
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
     * @var string $parentType
     *
     *  @ORM\Column(name="parent_type", type="string")
     */
    private $parentType;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRule", mappedBy="ruleset", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $rules;

    /**
     * @var boolean $defaultAllow
     *
     *
     * @ORM\Column(name="default_allow", type="boolean")
     */
    private $defaultAllow;

    public function __construct()
    {
        $this->rules = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setParentType($parentType)
    {
        if (!in_array($parentType, self::$validParentTypes)) {
            throw new \InvalidArgumentException(sprintf('Invalid parent type "%s" given', $parentType));
        }

        $this->parentType = $parentType;
    }

    public function getParentType()
    {
        return $this->parentType;
    }

    public function setRules($rules)
    {
        $this->rules = $rules;
    }

    public function getRules()
    {
        return $this->rules;
    }

    public function setDefaultAllow($defaultAllow)
    {
        $this->defaultAllow = $defaultAllow;
    }

    public function getDefaultAllow()
    {
        return $this->defaultAllow;
    }

    public static function getValidParentTypes()
    {
        return self::$validParentTypes;
    }
}
