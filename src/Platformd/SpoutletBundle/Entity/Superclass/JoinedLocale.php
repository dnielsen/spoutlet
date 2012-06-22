<?php

namespace Platformd\SpoutletBundle\Entity\Superclass;

use Doctrine\ORM\Mapping as ORM;
use Platformd\SpoutletBundle\Locale\JoinedLocaleInterface;

/**
 * @ORM\MappedSuperclass
 */
class JoinedLocale implements JoinedLocaleInterface
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
     * @var string $locale
     *
     * @ORM\Column(name="locale", type="string", length=255)
     */
    protected $locale;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
}