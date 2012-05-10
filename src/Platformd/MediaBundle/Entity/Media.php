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
     * @var \Platformd\SiteBundle\Entity\Site
     * @ORM\ManyToOne(targetEntity="Platformd\SiteBundle\Entity\Site")
     * @ORM\JoinColumn(onDelete="cascade", nullable=true)
     */
    protected $site;

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
     * @return \Platformd\SiteBundle\Entity\Site
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * @param \Platformd\SiteBundle\Entity\Site $site
     */
    public function setSite(Site $site)
    {
        $this->site = $site;
    }
}