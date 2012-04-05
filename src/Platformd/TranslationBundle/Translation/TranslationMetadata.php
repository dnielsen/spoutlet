<?php

namespace Platformd\TranslationBundle\Translation;

/**
 * Metadata about a translation key - which is loaded from metadata.yml
 */
class TranslationMetadata
{
    /**
     * The actual translation key that this metadata describes
     *
     * @var string
     */
    private $translationKey;

    /**
     * An optional description for this translation key
     *
     * @var string
     */
    private $description;

    /**
     * A "parent" translation key
     *
     * This is used in the admin - sometimes we have duplicate translation
     * keys. Eventually, we'd like to remove these duplicates. But for now,
     * we have a system of "parent" keys. Only the "parent" key is shown
     * and editable, but all of its children are updated on save.
     *
     * @var string
     */
    private $parentTranslationKey;

    /**
     * Some translation files are marked as disabled. We don't actually
     * use them, but we might be inheriting them from a bundle
     *
     * @var bool
     */
    private $isEnabled = true;

    public function __construct($translationKey)
    {
        $this->translationKey = $translationKey;
    }

    public function getTranslationKey()
    {
        return $this->translationKey;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getParentTranslationKey()
    {
        return $this->parentTranslationKey;
    }

    public function setParentTranslationKey($parentTranslationKey)
    {
        $this->parentTranslationKey = $parentTranslationKey;
    }

    /**
     * @return boolean
     */
    public function getIsEnabled()
    {
        return $this->isEnabled;
    }

    /**
     * @param boolean $isEnabled
     */
    public function setIsEnabled($isEnabled)
    {
        $this->isEnabled = $isEnabled;
    }
}
