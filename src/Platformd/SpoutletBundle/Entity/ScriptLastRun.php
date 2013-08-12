<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="script_last_run")
 */
class ScriptLastRun
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $id;

    /**
     * @ORM\Column(name="last_run", type="datetime", nullable=true)
     */
    protected $lastRun = null;

    public function __construct($id) {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setLastRun($value)
    {
        $this->lastRun = $value;
    }

    public function getLastRun()
    {
        return $this->lastRun;
    }
}
