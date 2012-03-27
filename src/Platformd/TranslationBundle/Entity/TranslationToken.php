<?php

namespace Platformd\TranslationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Represents a single translatable token
 *
 * A token consists of the actual source string (e.g. home_page) and the domain
 *
 * @ORM\Entity
 * @ORM\Table(name="pd_translation_token")
 */
class TranslationToken
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /** @ORM\Column(type="string", length=200) */
    protected $domain;

    /** @ORM\column(type="string", length=200, unique=true) */
    protected $token;

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
}