<?php

namespace Platformd\GiveawayBundle\Entity;

use Platformd\UserBundle\Entity\User;

use Doctrine\ORM\Mapping as ORM,
    Doctrine\Common\Collections\Collection,
    Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\HttpFoundation\File\UploadedFile,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * Platformd\GiveawayBundle\Entity\GiveawayPool
 *
 * @ORM\Entity()
 * @ORM\Table(name="giveaway_pool")
 */
class GiveawayPool
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
     * Many to one with Giveaway
     *
     * @var Giveaway
     * @ORM\ManyToOne(targetEntity="Platformd\GiveawayBundle\Entity\Giveaway", inversedBy="giveawayPools")
     */
    protected $giveaway;

    /**
     * One to Many with GiveawayPool
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="Platformd\GiveawayBundle\Entity\GiveawayKey", mappedBy="pool")
     */
    protected $giveawayKeys;

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

    public function __construct()
    {
        $this->giveawayKeys = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \Platformd\GiveawayBundle\Entity\Giveaway
     */
    public function getGiveaway()
    {
        return $this->giveaway;
    }

    /**
     * @param \Platformd\GiveawayBundle\Entity\Giveaway $giveaway
     */
    public function setGiveaway($giveaway)
    {
        $this->giveaway = $giveaway;
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

    /**
     * Take an "Symfony\Component\HttpFoundation\File\UploadedFile" and load its content
     * @return ArrayCollection
     */ 
    public function loadKeysFromFile()
    {
        if (is_null($this->keysfile)) {

            return;
        }

        try {
            $content = $this->keysfile->openFile();

            while (!$content->eof()) {
                $value = $content->fgets();
                if (!$value || empty($value)) {
                    continue;
                }

                $key = new GiveawayKey($value);
                $key->setPool($this);
                $this->giveawayKeys->add($key);
            }
            
            return $this->giveawayKeys;
        } catch(\RuntimeException $e) {
            // Handle error
        }
    }
}