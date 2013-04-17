<?php

namespace Platformd\GiveawayBundle\Entity;

use Platformd\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Platformd\SpoutletBundle\Entity\AbstractEvent;
use Platformd\GiveawayBundle\Entity\GiveawayPool;
use Gedmo\Mapping\Annotation as Gedmo;

use Symfony\Component\Validator\Constraints as Assert;
use Platformd\SpoutletBundle\Entity\Site;

/**
 * Platformd\GiveawayBundle\Entity\Giveaway
 *
 * @ORM\Entity(repositoryClass="Platformd\GiveawayBundle\Entity\GiveawayRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Giveaway extends AbstractEvent
{
    // the traditional key giveaway type
    const TYPE_KEY_GIVEAWAY = 'key_giveaway';

    // the machine-submit giveaway type
    const TYPE_MACHINE_CODE_SUBMIT = 'machine_code_submit';

    const TYPE_TEXT_PREFIX = 'giveaway.type.';

    const REDEMPTION_LINE_PREFIX = '* ';

    /**
     * One to Many with GiveawayPool
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="Platformd\GiveawayBundle\Entity\GiveawayPool", mappedBy="giveaway")
     */
    protected $giveawayPools;

    /**
     * This is a raw HTML field, but with a special format.
     *
     * Each line will be exploded into an array, and used for numbered
     * instructions on the giveaway.
     *
     * @ORM\Column(type="text")
     *
     * @var string
     */
    protected $redemptionInstructions;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\GiveawayBundle\Entity\GiveawayTranslation", mappedBy="translatable", cascade={"all"})
     */
    protected $translations;

    /**
     * A string enum status
     *
     * @var string
     * @ORM\Column(type="string", length=15)
     */
    protected $status = 'disabled';

    /**
     * @var string
     * @ORM\Column(type="string", length=30)
     */
    protected $giveawayType = self::TYPE_KEY_GIVEAWAY;

    protected $currentLocale;

    protected $defaultLocale = 'en';

    /**
     * Key of valid status to a text translation key for that status
     *
     * @var array
     */
    static protected $validStatuses = array(
        // totally disabled
        'disabled' => 'platformd.giveaway.status.disabled',
        // active but with zero keys
        'inactive' => 'platformd.giveaway.status.inactive',
        // totally awesome active
        'active' => 'platformd.giveaway.status.active',
    );

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $displayRemainingKeysNumber = true;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $backgroundImagePath;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $backgroundLink;

    /**
     * @Assert\File(
     *   maxSize="6000000",
     *   mimeTypes={"image/png", "image/jpeg", "image/jpg", "image/gif"}
     * )
     */
    protected $backgroundImage;

    public function __construct()
    {
        parent::__construct();

        // auto-publish, this uses the "status" field instead
        $this->published = true;
        $this->giveawayPools = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }

    public function __call($method, array $arguments = array())
    {
        $translation = $this->translate();

        $value = null;
        if ($translation) {
            $value = call_user_func_array(array($translation, $method), $arguments);
        }

        return $value;
    }

    private function translate(Site $locale = null)
    {
        $currentLocale = $locale ?: $this->getCurrentLocale();

        return $this->translations->filter(function($translation) use($currentLocale) {
            return $translation->getLocale() === $currentLocale;
        })->first();
    }

    public function getName()
    {
        $translation = $this->translate();

        $value = null;
        if ($translation) {
            $value = $translation->getName();
        }

        return $value ?: $this->name;
    }

    public function getContent()
    {
        $translation = $this->translate();

        $value = null;
        if ($translation) {
            $value = $translation->getContent();
        }

        return $value ?: $this->content;
    }

    public function getCurrentLocale()
    {
        return $this->currentLocale ?: $this->defaultLocale;
    }

    public function setCurrentLocale(Site $locale = null)
    {
        $this->currentLocale = $locale;
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function getTranslations()
    {
        return $this->translations;
    }

    public function addTranslation(GiveawayTranslation $translation)
    {
        $this->translations->add($translation);
        $translation->setTranslatable($this);
    }

    public function setTranslations(Collection $translations)
    {
        foreach ($translations as $translation) {
            $this->addTranslation($translation);
        }
    }

    public function removeTranslation(GiveawayTranslation $translation)
    {
        $this->translations->removeElement($translation);
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getGiveawayPools()
    {
        return $this->giveawayPools;
    }

    /**
     * @param \Doctrine\Common\Collections\ArrayCollection $giveawayPools
     */
    public function setGiveawayPools($giveawayPools)
    {
        $this->giveawayPools = $giveawayPools;
    }

    /**
     * @return string
     */
    public function getRedemptionInstructions()
    {
        return $this->redemptionInstructions;
    }

    /**
     * @param string $redemptionInstructions
     */
    private function setRedemptionInstructions($redemptionInstructions)
    {
        $this->redemptionInstructions = $redemptionInstructions;
    }

    /**
     * Explodes the redemptionInstructions text by new line and removing the prefix:
     *
     * The literal source text (with opening asterisks) looks like this:
     *
     *  * foo
     *  * bar
     *
     * @return array
     */
    public function getRedemptionInstructionsArray()
    {
        $arr = explode(self::REDEMPTION_LINE_PREFIX, $this->getRedemptionInstructions());

        foreach ($arr as $lineNo => $line) {
            // remove trailing whitespace
            $arr[$lineNo] = trim($line);

            // unset the whole dang entry if it's empty
            if (empty($line)) {
                unset($arr[$lineNo]);
            }
        }

        // re-index the array
        $arr = array_values($arr);

        // make sure we have at least 6 entries
        while (count($arr) < 6) {
            $arr[] = '';
        }

        return $arr;
    }

    /**
     * Allows you to set the redemption instructions where each step is
     * an item in an array
     *
     * @param array $instructions
     */
    public function setRedemptionInstructionsArray(array $instructions)
    {
        $str = '';
        foreach ($instructions as $line) {
            // only store the line if it's non-blank
            if ($line) {
                $str .= self::REDEMPTION_LINE_PREFIX . $line."\n";
            }
        }

        $this->setRedemptionInstructions(trim($str));
    }

    /**
     * Returns the redemption instructions array, but without blank lines
     *
     * @return array
     */
    public function getCleanedRedemptionInstructionsArray()
    {
        $translation = $this->translate();

        $value = null;
        if ($translation) {
            $value = $translation->getCleanedRedemptionInstructionsArray();

            if ($value) {
                return $value;
            }
        }

        $cleaned = array();
        foreach ($this->getRedemptionInstructionsArray() as $item) {
            if ($item) {
                $cleaned[] = $item;
            }
        }

        return $cleaned;
    }

    /**
     * Makes sure the redemption instructions are trimmed
     *
     * @ORM\prePersist
     * @ORM\preUpdate
     */
    public function trimRedemptionInstructions()
    {
        $this->setRedemptionInstructions(trim($this->getRedemptionInstructions()));
    }

    /**
     * Add an user
     *
     * @param \Platformd\GiveawayBundle\Entity\GiveawayPool $pool
     */
    public function addUser(GiveawayPool $pool)
    {
        $this->giveawayPools->add($pool);
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        if (!$status) {
            return;
        }

        if (!in_array($status, array_keys(self::$validStatuses))) {
            throw new \InvalidArgumentException(sprintf('Invalid status "%s" given', $status));
        }

        $this->status = $status;
    }

    /**
     * Returns the "text" for the current status
     *
     * The text is actually just a translation key
     *
     * @return string
     */
    public function getStatusText()
    {
        return self::$validStatuses[$this->getStatus() ?: 'disabled'];
    }

    /**
     * Returns a key-value pair of valid status keys and their text translation
     *
     * Useful in forms
     *
     * @return array
     */
    static public function getValidStatusesMap()
    {
        return self::$validStatuses;
    }

    /**
     * @return bool
     */
    public function isDisabled()
    {
        return $this->getStatus() == 'disabled';
    }

    public function isActive()
    {
        return $this->getStatus() == 'active';
    }

    public function setAsActive()
    {
        $this->setStatus('active');
    }

    /**
     * Returns the "active" pool, which is just the first one we find that
     * is indeed active
     *
     * @return \Platformd\GiveawayBundle\Entity\GiveawayPool
     */
    public function getActivePool()
    {
        foreach($this->getGiveawayPools() as $pool) {
            if ($pool->getIsActive()) {
                return $pool;
            }
        }
    }

    /**
     * Returns the route name to this item's show page
     *
     * @deprecated It's use should be replaced by the LinkableInterface
     * @return string
     */
    public function getShowRouteName()
    {
        return 'giveaway_show';
    }

    /**
     * @return string
     */
    public function getGiveawayType()
    {
        return $this->giveawayType;
    }

    public function giveawayTypeText()
    {
        return self::TYPE_TEXT_PREFIX.$this->getGiveawayType();
    }

    /**
     * @param string $giveawayType
     */
    public function setGiveawayType($giveawayType)
    {
        if ($giveawayType != self::TYPE_KEY_GIVEAWAY && $giveawayType != self::TYPE_MACHINE_CODE_SUBMIT) {
            throw new \InvalidArgumentException(sprintf('Invalid giveaway type "%s" given', $giveawayType));
        }

        $this->giveawayType = $giveawayType;
    }

    /**
     * @return bool
     */
    public function getShowKeys()
    {
        // show the keys if its a traditional key giveaway
        return $this->getGiveawayType() == self::TYPE_KEY_GIVEAWAY && $this->displayRemainingKeysNumber;
    }

    /**
     * Whether or not a user is able to freely register for giveaway keys for this giveaway
     *
     * @return bool
     */
    public function allowKeyFetch()
    {
        return self::TYPE_KEY_GIVEAWAY == $this->getGiveawayType();
    }

    /**
     * Whether or not the user can submit a machine code for this giveaway
     *
     * @return bool
     */
    public function allowMachineCodeSubmit()
    {
        return self::TYPE_MACHINE_CODE_SUBMIT == $this->getGiveawayType();
    }

    static public function getTypeChoices()
    {
        return array(
            self::TYPE_KEY_GIVEAWAY => self::TYPE_TEXT_PREFIX.self::TYPE_KEY_GIVEAWAY,
            self::TYPE_MACHINE_CODE_SUBMIT => self::TYPE_TEXT_PREFIX.self::TYPE_MACHINE_CODE_SUBMIT,
        );
    }

    /**
     * Returns the name of the route used to link to this object
     *
     * @return string
     */
    public function getLinkableRouteName()
    {
        return 'giveaway_show';
    }

    public function isDisplayRemainingKeysNumber()
    {
        return $this->displayRemainingKeysNumber;
    }

    public function setDisplayRemainingKeysNumber($displayRemainingKeysNumber)
    {
        $this->displayRemainingKeysNumber = $displayRemainingKeysNumber;
    }

    public function getBackgroundImagePath()
    {
        if ($translation = $this->translate()) {
            if ($path = $translation->getBackgroundImagePath()) {
                return $path;
            }
        }

        return $this->backgroundImagePath;
    }

    public function setBackgroundImagePath($backgroundImagePath)
    {
        $this->backgroundImagePath = $backgroundImagePath;
    }

    public function getBackgroundImage()
    {
        return $this->backgroundImage;
    }

    public function setBackgroundImage($backgroundImage)
    {
        $this->backgroundImage = $backgroundImage;
    }

    public function getBackgroundLink($bypassTranslations = false)
    {
        if (!$bypassTranslations && $translation = $this->translate()) {
            if ($link = $translation->getBackgroundLink()) {
                return $link;
            }
        }

        return $this->backgroundLink;
    }

    public function setBackgroundLink($backgroundLink)
    {
        $this->backgroundLink = $backgroundLink;
    }

    public function getBannerImage($bypassTranslations = false)
    {
        if (!$bypassTranslations && $translation = $this->translate()) {
            if ($path = $translation->getBannerImage()) {
                return $path;
            }
        }

        return $this->bannerImage;
    }

    public function setBannerImage($bannerImage)
    {
        $this->bannerImage = $bannerImage;
    }
}
