<?php

namespace Platformd\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Knp\MediaBundle\Entity\Media as BaseMedia;
use Knp\MediaBundle\Model\MediaOwnerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Platformd\SiteBundle\Entity\Site;

/**
 * Our media entity
 *
 * @ORM\Entity(repositoryClass="Knp\MediaBundle\Entity\MediaRepository")
 * @ORM\Table(name="pd_media")
 */
class Media extends BaseMedia implements MediaOwnerInterface
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
     * The person who uploaded this media
     *
     * @var \Platformd\UserBundle\Entity\User
     * @ORM\ManyToOne(targetEntity="Platformd\UserBundle\Entity\User", cascade={"delete"})
     * @ORM\JoinColumn(onDelete="cascade")
     */
    protected $owner;

    /**
     * @var string $locale
     *
     * @ORM\Column(name="locale", type="string", length="10", nullable=false)
     */
    protected $locale;

    /**
     * @var boolean $removed
     * @ORM\Column(name="removed", type="boolean")
     */
    protected $removed = false;

    /**
     * @return \Platformd\UserBundle\Entity\User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param \Platformd\UserBundle\Entity\User $owner
     */
    public function setOwner(UserInterface $owner)
    {
        $this->owner = $owner;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

     /**
     * @return boolean
     */
    public function getRemoved()
    {
        return $this->removed;
    }

    /**
     * @param boolean $removed
     */
    public function setRemoved($removed)
    {
        $this->removed = $removed;
    }
}
