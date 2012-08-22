<?php

namespace Platformd\SpoutletBundle\Entity\Superclass;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\HttpFoundation\File\UploadedFile,
Symfony\Component\Validator\Constraints as Assert;

/**
 * A mapped super class that all other pools inherit from
 *
 * @ORM\MappedSuperclass
 */
abstract class Pool
{
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
}
