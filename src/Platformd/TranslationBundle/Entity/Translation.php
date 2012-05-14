<?php

namespace Platformd\TranslationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

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
     * @ORM\ManyToOne(targetEntity="TranslationToken", fetch="EAGER", inversedBy="translations")
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
}