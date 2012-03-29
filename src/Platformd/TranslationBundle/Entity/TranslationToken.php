<?php

namespace Platformd\TranslationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Represents a single translatable token
 *
 * A token consists of the actual source string (e.g. home_page) and the domain
 *
 * @ORM\Entity(repositoryClass="Platformd\TranslationBundle\Entity\Repository\TranslationTokenRepository")
 * @ORM\Table(
 *      name="pd_translation_token",
 *      uniqueConstraints={@ORM\UniqueConstraint(name="token_domain_idx", columns={"token", "domain"})}
 * )
 */
class TranslationToken
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=200)
     */
    protected $domain;

    /**
     * @ORM\column(type="string", length=200, unique=false)
     */
    protected $token;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updated;

    /**
     * Whether or not this token is found in the message files
     *
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $isInMessagesFile = false;

    public function getId()
    {
        return $this->id;
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime $created
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \DateTime $updated
     */
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;
    }

    /**
     * @return boolean
     */
    public function getIsInMessagesFile()
    {
        return $this->isInMessagesFile;
    }

    /**
     * @param boolean $isInMessagesFile
     */
    public function setIsInMessagesFile($isInMessagesFile)
    {
        $this->isInMessagesFile = $isInMessagesFile;
    }
}