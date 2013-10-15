<?php

namespace Platformd\GiveawayBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Platformd\GiveawayBundle\Entity\CodeAssignment
 *
 * @ORM\Table(name="code_assignment")
 * @ORM\Entity()
 */
class CodeAssignment
{
    const TYPE_USERS = 'users';
    const TYPE_LAN   = 'lancenters';

    private static $validTypes = array(
        self::TYPE_USERS => 'Users',
        self::TYPE_LAN   => 'LAN Centers',
    );

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @Assert\Url
     * @Assert\NotBlank()
     * @ORM\Column(name="url", type="string", length="255")
     */
    private $url;

    /**
     * @ORM\Column(name="type", type="string", length="255")
     */
    private $type;

    /**
     * @Assert\File(maxSize="6000000")
     */
    private $codesFile;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\GiveawayBundle\Entity\CodeAssignmentCode", mappedBy="assignment")
     */
    private $codes;

    public function __construct() {
        $this->codes = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($value)
    {
        $this->name = $value;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($value)
    {
        $this->url = $value;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($value)
    {
        if ($value && !isset(self::$validTypes[$value])) {
            throw new \InvalidArgumentException(sprintf('Invalid type passed: "%s"', $value));
        }

        $this->type = $value;
    }

    public function getCodesFile()
    {
        return $this->codesFile;
    }

    public function setCodesFile($value)
    {
        $this->codesFile = $value;
    }

    public function getCodes()
    {
        return $this->codes;
    }

    public function setCodes($value)
    {
        $this->codes = $value;
    }

    public function addCode($code)
    {
        $code->setAssignment($this);
        $this->codes->add($code);
    }

    static public function getValidTypes()
    {
        return self::$validTypes;
    }
}
