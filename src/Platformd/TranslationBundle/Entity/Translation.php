<?php

namespace Platformd\TranslationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Platformd\TranslationBundle\Entity\Repository\TranslationRepository")
 * @ORM\Table(name="pd_translation")
 */
class Translation
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * The source translation token being translated
     *
     * @ORM\ManyToOne(targetEntity="TranslationToken", fetch="EAGER")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $translationToken;

    /**
     * The language that this applies to
     *
     * @ORM\Column(type="string", length="10")
     */
    protected $language;

    /**
     * The actual translation
     *
     * @ORM\Column(type="text")
     */
    protected $translation;

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \Platformd\TranslationBundle\Entity\TranslationToken
     */
    public function getTranslationToken()
    {
        return $this->translationToken;
    }

    public function setTranslationToken(TranslationToken $translationToken)
    {
        $this->translationToken = $translationToken;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage($language)
    {
        $this->language = $language;
    }

    public function getTranslation()
    {
        return $this->translation;
    }

    public function setTranslation($translation)
    {
        $this->translation = $translation;
    }
}