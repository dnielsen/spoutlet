<?php
/**
 * Created by PhpStorm.
 * User: gimballock
 * Date: 12/4/13
 * Time: 12:22 PM
 */

namespace Platformd\IdeaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\ArrayCollection;


/**
 * @ORM\Table(name="EntrySetRegistry")
 * @ORM\Entity
 */
class EntrySetRegistry {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $scope;

    /**
     * @ORM\Column(type="integer")
     */
    protected $containerId;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\IdeaBundle\Entity\EntrySet", mappedBy="container", cascade={"persist"})
     */
    protected $entrySets;

    /**
     * Constructor
     */
    public function __construct($container)
    {
        $this->entrySets = new ArrayCollection();
        $this->scope = get_class($container);
        $this->containerId = $container->getId();
    }

    /**
     * @return mixed
     */
    public function getContainerId()
    {
        return $this->containerId;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @return mixed
     */
    public function getEntrySets()
    {
        return $this->entrySets;
    }
} 