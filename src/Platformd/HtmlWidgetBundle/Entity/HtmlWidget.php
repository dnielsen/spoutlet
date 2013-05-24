<?php

namespace Platformd\HtmlWidgetBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Platformd\HtmlWidgetBundle\Validator\HtmlWidgetSlug as AssertUniqueSlug;
use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\Validator\ExecutionContext;

/**
 * Platformd\HtmlWidgetBundle\Entity\HtmlWidget
 *
 * @ORM\Table(name="html_widget")
 * @ORM\Entity(repositoryClass="Platformd\HtmlWidgetBundle\Entity\HtmlWidgetRepository")
 * @AssertUniqueSlug()
 * @Assert\Callback(methods={"validateSlug"})
 */
class HtmlWidget
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotNull
     */
    private $name;

    /**
     * @ORM\Column(name="slug", type="string", length=255)
     * @Assert\Regex(pattern="/^[A-Za-z0-9\-]+$/", message="This can only contain letters, numbers and dashes (-)")
     */
    private $slug;

    /**
     * @ORM\Column(name="content", type="text")
     * @Assert\NotNull
     */
    private $content;

    /**
     * @ORM\Column(name="notes", type="string", length=255)
     * @Assert\NotNull
     */
    private $notes;

    /**
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @ORM\JoinTable(name="html_widget_site")
     */
    private $sites;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;

    public function getId()
    {
        return $this->id;
    }

    public function setName($value)
    {
        $this->name = $value;

        if (!$this->getSlug()) {
            $slug = Urlizer::urlize($value);

            $this->setSlug($slug);
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function setSlug($value)
    {
        if (!$value) {
            return;
        }

        $this->slug = $value;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setContent($value)
    {
        $this->content = $value;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setNotes($value)
    {
        $this->notes = $value;
    }

    public function getNotes()
    {
        return $this->notes;
    }

    public function getSites()
    {
        return $this->sites;
    }

    public function setSites($value)
    {
        $this->sites = $value;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($value)
    {
        $this->createdAt = $value;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt($value)
    {
        $this->updatedAt = $value;
    }

    public function __toString()
    {
        return (string) $this->getName();
    }

    public function validateSlug(ExecutionContext $executionContext)
    {
        if (!$this->getSlug()) {
            $slug = Urlizer::urlize($this->getName());

            if (!$slug) {
                $oldPath = $executionContext->getPropertyPath();
                $propertyPath = $oldPath . '.name';
                $executionContext->setPropertyPath($propertyPath);

                $executionContext->addViolation(
                    "Please enter a valid name for your widget.",
                    array(),
                    "name"
                );

                $executionContext->setPropertyPath($oldPath);
            }
        }
    }
}
