<?php
namespace Platformd\IdeaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 * @ORM\Table(name="links")
 */
class Link
{

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $title;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $linkDescription;

    /**
     * @ORM\Column(type="string", length=2048)
     */
    protected $url;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $type;

    /**
     * @ORM\ManyToOne(targetEntity="Idea", inversedBy="links")
     */
    protected $idea;


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
     * Set url
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set idea
     *
     * @param Platformd\IdeaBundle\Entity\Idea $idea
     */
    public function setIdea(\Platformd\IdeaBundle\Entity\Idea $idea)
    {
        $this->idea = $idea;
    }

    /**
     * Get idea
     *
     * @return Platformd\IdeaBundle\Entity\Idea
     */
    public function getIdea()
    {
        return $this->idea;
    }

    /**
     * Set type
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     */
    public function setLinkDescription($description)
    {
        $this->linkDescription = $description;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getLinkDescription()
    {
        return $this->linkDescription;
    }
}
