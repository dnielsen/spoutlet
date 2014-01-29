<?php
/**
 * Created by PhpStorm.
 * User: gimballock
 * Date: 1/28/14
 * Time: 4:35 PM
 */

namespace Platformd\IdeaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="sponsor")
 */
class Sponsor {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * A name for this sponsor
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $name;

    /**
     * Link to sponsor's page
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $url;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User", inversedBy="sponsors")
     */
    protected $creator;

    /**
     * @ORM\OneToOne(targetEntity="Platformd\MediaBundle\Entity\Media", cascade={"remove", "persist"})
     */
    protected $image;

    /**
     * @ORM\OneToOne(targetEntity="Platformd\IdeaBundle\Entity\SponsorRegistry", cascade={"remove", "persist"})
     */
    protected $sponsorRegistration;

    /**
     * @param mixed $creator
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;
    }

    /**
     * @return mixed
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param mixed $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
} 