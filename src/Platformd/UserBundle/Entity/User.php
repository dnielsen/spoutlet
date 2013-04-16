<?php

namespace Platformd\UserBundle\Entity;

use FOS\UserBundle\Entity\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

use EWZ\Bundle\RecaptchaBundle\Validator\Constraints as Recaptcha;

/**
 * Platformd\UserBundle\Entity\User
 *
 * @ORM\Table(name="fos_user")
 * @ORM\Entity(repositoryClass="Platformd\UserBundle\Entity\UserRepository")
 * @ORM\haslifecyclecallbacks
 */
class User extends BaseUser
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
     * @var String $firstname
     *
     * @ORM\Column(type="string", length="255", nullable=true)
     *
     * @Assert\NotBlank(groups={"Registration"}, message="first_name_not_blank")
     * @Assert\MinLength(limit="1", groups={"Registration"})
     * @Assert\MaxLength(limit="255", groups={"Registration"})
     */
    protected $firstname;

    /**
     * @var String $lastname
     *
     * @ORM\Column(type="string", length="255", nullable=true)
     *
     * @Assert\NotBlank(groups={"Registration"}, message="last_name_not_blank")
     * @Assert\MinLength(limit="1", groups={"Registration"})
     * @Assert\MaxLength(limit="255", groups={"Registration"})
     */
    protected $lastname;

    /**
     * @var Datetime $birthdate
     *
     * @ORM\Column(type="date", nullable=true)
     *
     * @Assert\NotBlank(groups={"Registration"}, message="birthdate_not_blank")
     * @Assert\Date(groups={"Registration"})
     */
    protected $birthdate;

    /**
     * @var String $phone_number
     *
     * @ORM\Column(name="phone_number", type="string", length="255", nullable=true)
     */
    protected $phoneNumber;

    /**
     * @var String $country
     *
     * @ORM\Column(type="string", length="255", nullable=true)
     *
     * @Assert\NotBlank(groups={"Registration"})
     */
    protected $country;

    /**
     * @var String $state
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\NotBlank(groups={"Registration"}, message="state_not_blank")
     */
    protected $state;

    /**
     * @var boolean $has_alienware_system
     *
     * @ORM\Column(name="has_alienware_system", type="boolean", nullable=true)
     * @Assert\NotNull
     */
    protected $hasAlienwareSystem = false;

    /**
     * @var String $latest_news_source
     *
     * @ORM\Column(name="latest_news_source", type="string", length="255", nullable=true)
     */
    protected $latestNewsSource;

    /**
     * @var bool $subscribe_gaming_news
     *
     * @ORM\Column(name="subscribed_gaming_news", type="boolean", nullable=true)
     */
    protected $subscribedGamingNews;

    /**
     * This is the checkbox for:
     *     Subscribe me to receive news on the latest giveaways, contests,
     *     events and more from Alienware Arena via email.
     *
     * @var bool
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $subscribedAlienwareEvents;

    /**
     * @var Boolean $terms_accepted
     *
     * @Assert\True(message="You must accept the terms and conditions and the privacy policy", groups={"Registration"})
     */
    protected $termsAccepted;

    /**
     * @var String $avatar
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $avatar;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $cevoAvatarUrl;

    /**
     * @var String $type
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $type;

    /**
     * @var String $manufacturer
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $manufacturer;

    /**
     * @var String $operatingSystem
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $operatingSystem;

    /**
     * @var String $cpu
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $cpu;

    /**
     * @var String $memory
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $memory;

    /**
     * @var String $videoCard
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $videoCard;

    /**
     * @var String $soundCard
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $soundCard;

    /**
     * @var String $hardDrive
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $hardDrive;

    /**
     * @var String $headPhones
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $headphones;

    /**
     * @var String $mouse
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $mouse;

    /**
     * @var String $mousePad
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $mousePad;

    /**
     * @var String $keyboard
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $keyboard;

    /**
     * @var String $monitor
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $monitor;

    /**
     * @var Boolean $avatarApproved
     *
     * @ORM\Column(type="boolean")
     */
    protected $avatar_approved = false;

    /**
     * @var string The locale in which this user registered
     * @ORM\Column(type="string", length=2, nullable=true)
     */
    protected $locale;

    /**
     * One to Many with GiveawayKey
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="Platformd\GiveawayBundle\Entity\GiveawayKey", mappedBy="user")
     */
    protected $giveawayKeys;

    /**
     * @var datetime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @var datetime $updated
     *
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updated;

    /**
     * @Assert\File(maxSize="6000000", mimeTypes={"image/png", "image/jpeg", "image/jpg"}, groups={"Profile"})
     */
    public $file;

    /**
     * Little flag so we know if the avatar was changed on this request
     *
     * @var bool
     */
    protected $avatarChanged = false;

    /**
     * The id for this user in CEVO's database
     *
     * @var string
     * @ORM\Column(type="integer", nullable=true)
     */
    private $cevoUserId;

     /**
      * @ORM\OneToMany(targetEntity="Platformd\GroupBundle\Entity\GroupMembershipAction", mappedBy="user", cascade={"persist"})
      * @ORM\JoinColumn(onDelete="SET NULL")
      */
    private $groupMembershipActions;

    /**
      * @ORM\OneToMany(targetEntity="Platformd\SpoutletBundle\Entity\LoginRecord", mappedBy="user", cascade={"persist"})
      * @ORM\JoinColumn(onDelete="SET NULL")
      * @ORM\OrderBy({"dateTime" = "DESC"})
      */
    private $loginRecords;

    public function __construct()
    {
        parent::__construct();
        $this->events = new ArrayCollection();
        $this->giveawayKeys = new ArrayCollection();
        $this->groupMembershipActions = new ArrayCollection();
        $this->loginRecords = new ArrayCollection();
    }

    /**
     * Return true if the user's avatar has been approved
     *
     * @return Boolean
     */
    public function isAvatarApproved()
    {

        return $this->avatar_approved;
    }

    public function approveAvatar()
    {
        $this->avatar_approved = true;
    }

    public function disapproveAvatar()
    {
        $this->avatar_approved = false;
    }

    public function getAbsolutePath()
    {
        if (!$this->avatar) {

            return null;
        }

        return $this->getUploadRootDir().'/'.$this->avatar;
    }

    /**
     * @ORM\PostRemove()
     */
    public function removeUpload()
    {
        if ($file = $this->getAbsolutePath()) {
            unlink($file);
        }
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get user's lastname
     *
     * @return String
     */
    public function getLastname()
    {

        return $this->lastname;
    }

    /**
     * Set user's firstname
     *
     * @param String $lastname
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }

    /**
     * Get user's firstname
     *
     * @return String
     */
    public function getFirstname()
    {

        return $this->firstname;
    }

    /**
     * Set user's firstname
     *
     * @param String $firstname
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }

    /**
     * Get user's birthdate
     *
     * @return \Datetime
     */
    public function getBirthdate()
    {

        return $this->birthdate;
    }

    /**
     * Set user's birthdate
     *
     * @param \Datetime $date
     */
    public function setBirthdate(\Datetime $date = null)
    {
        $this->birthdate = $date;
    }

    /**
     * Get user's phone number
     *
     * @return String
     */
    public function getPhoneNumber()
    {

        return $this->phoneNumber;
    }

    /**
     * Set user's phone number
     *
     * @param String $phoneNumber
     */
    public function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * Get user's country
     *
     * @return String
     */
    public function getCountry()
    {

        return $this->country;
    }

    /**
     * Set user's country
     *
     * @param String $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * Get user's state
     *
     * @return String
     */
    public function getState()
    {

        return $this->state;
    }

    /**
     * Set user's state
     *
     * @param String $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * Do the user have an Alienware system ?
     *
     * @return boolean
     */
    public function getHasAlienwareSystem()
    {

        return $this->hasAlienwareSystem;
    }

    /**
     * Set if the user has an alienware system
     *
     * @param boolean $flag
     */
    public function setHasAlienwareSystem($flag)
    {
        $this->hasAlienwareSystem = $flag;
    }

    /**
     * Get latest news' source (where has the user heard about alienware last)
     *
     * @return String
     */
    public function getLatestNewsSource()
    {

        return $this->latestNewsSource;
    }

    /**
     * Set latest news' source
     *
     * @param String $source
     */
    public function setLatestNewsSource($source)
    {
        $this->latestNewsSource = $source;
    }

    /**
     * Get if the user has subscribed to alienware arena news
     *
     * @return boolean
     */
    public function getSubscribedArenaNews()
    {

        return $this->subscribedArenaNews;
    }

    /**
     * Set if the user has subscribed to alienware arena news
     *
     * @param boolean $subscribed
     */
    public function setSubscribedArenaNews($subscribed)
    {
        $this->subscribedArenaNews = $subscribed;
    }

    /**
     * Get if the user has subscribed to alienware/dell gaming news
     *
     * @return boolean
     */
    public function getSubscribedGamingNews()
    {

        return $this->subscribedGamingNews;
    }

    /**
     * Set if the user has subscribed to alienware/dell gaming news
     *
     * @param boolean $subscribed
     */
    public function setSubscribedGamingNews($subscribed)
    {
        $this->subscribedGamingNews = $subscribed;
    }

    /**
     * Get if the user has accepted terms
     *
     * @return boolean
     */
    public function getTermsAccepted()
    {

        return $this->termsAccepted;
    }

    /**
     * Set if the user has accepted terms
     *
     * @param boolean $accepted
     */
    public function setTermsAccepted($accepted)
    {
        $this->termsAccepted = $accepted;
    }

    public function getAvatar()
    {

        return $this->avatar;
    }

    /**
     * Returns the avatar path, with respect to it being approved or not.
     *
     * If the avatar is not approved, false is returned.
     * This logic here is repeated elsewhere, but should use this.
     *
     * @return string
     */
    public function getApprovedAvatar()
    {
        if (!$this->isAvatarApproved()) {
            return false;
        }

        return $this->getAvatar();
    }

    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;

        // set a flag so we can give the user a message
        $this->avatarChanged = true;
    }

    public function getWebPath()
    {
        return null === $this->avatar ? null : $this->getUploadDir().'/'.$this->avatar;
    }

    public function getType()
    {

        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getManufacturer()
    {

        return $this->manufacturer;
    }

    public function setManufacturer($manufacturer)
    {
        $this->manufacturer = $manufacturer;
    }

    public function getOperatingSystem()
    {

        return $this->operatingSystem;
    }

    public function setOperatingSystem($os)
    {
        $this->operatingSystem = $os;
    }

    public function getCPU()
    {

        return $this->cpu;
    }

    public function setCPU($cpu)
    {
        $this->cpu = $cpu;
    }

    public function getMemory()
    {

        return $this->memory;
    }

    public function setMemory($memory)
    {
        $this->memory = $memory;
    }

    public function getVideoCard()
    {

        return $this->videoCard;
    }

    public function setVideoCard($card)
    {
        $this->videoCard = $card;
    }

    public function getSoundCard()
    {

        return $this->soundCard;
    }

    public function setSoundCard($card)
    {
        $this->soundCard = $card;
    }

    public function getHardDrive()
    {

        return $this->hardDrive;
    }

    public function setHardDrive($drive)
    {
        $this->hardDrive = $drive;
    }

    public function getHeadphones()
    {

        return $this->headphones;
    }

    public function setHeadphones($phones)
    {
        $this->headphones = $phones;
    }

    public function getMouse()
    {

        return $this->mouse;
    }

    public function setMouse($mouse)
    {
        $this->mouse = $mouse;
    }

    public function getMousePad()
    {

        return $this->mousePad;
    }

    public function setMousePad($pad)
    {
        $this->mousePad = $pad;
    }

    public function getKeyboard()
    {

        return $this->keyboard;
    }

    public function setKeyboard($keyboard)
    {
        $this->keyboard = $keyboard;
    }

    public function getMonitor()
    {

        return $this->monitor;
    }

    public function setMonitor($monitor)
    {
        $this->monitor = $monitor;
    }

    public function getIsOrganizer()
    {
        return $this->hasRole('ROLE_ORGANIZER');
    }

    public function setIsOrganizer($is_organizer)
    {
        $this->switchRole('ROLE_ORGANIZER', $is_organizer);
    }

    public function getIsSuperAdmin()
    {
        return $this->hasRole('ROLE_SUPER_ADMIN');
    }

    public function setIsSuperAdmin($is_super_admin)
    {
        $this->switchRole('ROLE_SUPER_ADMIN', $is_super_admin);
    }

    /**
     * Used on the user admin form to choose one role
     *
     * This whole system stinks a bit and needs refactoring.
     * Also see EditUserFormType
     *
     * @param string $role either ROLE_ORGANIZER or ROLE_SUPER_ADMIN
     * @throws \InvalidArgumentException
     */
    public function setAdminLevel($role)
    {
        if ($role == 'ROLE_ORGANIZER') {
            $this->setIsOrganizer(true);
            $this->setIsSuperAdmin(false);
        } elseif ($role == 'ROLE_SUPER_ADMIN') {
            $this->setIsOrganizer(false);
            $this->setIsSuperAdmin(true);
        } elseif ($role == 'ROLE_PARTNER') {
            $this->setIsOrganizer(false);
            $this->setIsSuperAdmin(false);
            $this->switchRole('ROLE_PARTNER', true);
        } else {
            $this->setIsOrganizer(false);
            $this->setIsSuperAdmin(false);
        }
    }

    /**
     * Used on the user edit from - where you are an organizer or a super admin
     */
    public function getAdminLevel()
    {
        if ($this->hasRole('ROLE_ORGANIZER')) {
            return 'ROLE_ORGANIZER';
        } elseif ($this->hasRole('ROLE_PARTNER')) {
            return 'ROLE_PARTNER';
        } elseif ($this->hasRole('ROLE_SUPER_ADMIN')) {
            return 'ROLE_SUPER_ADMIN';
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getAdminLevelString()
    {
        if ($this->hasRole('ROLE_ORGANIZER')) {
            return 'Limited admin';
        } elseif ($this->hasRole('ROLE_PARTNER')) {
            return 'Dell Partner';
        } elseif ($this->hasRole('ROLE_SUPER_ADMIN')) {
            return 'Full Admin';
        } else {
            return 'no admin';
        }
    }

    private function switchRole($role, $bool)
    {
        if ($bool) {
            $this->addRole($role);
        } else {
            $this->removeRole($role);
        }
    }

    protected function getUploadRootDir()
    {

        return __DIR__.'/../../../../web/'.$this->getUploadDir();
    }

    protected function getUploadDir()
    {

        return '/uploads/avatars';
    }

    /**
     * @return boolean
     */
    public function getSubscribedAlienwareEvents()
    {
        return $this->subscribedAlienwareEvents;
    }

    /**
     * @param boolean $subscribedAlienwareEvents
     */
    public function setSubscribedAlienwareEvents($subscribedAlienwareEvents)
    {
        $this->subscribedAlienwareEvents = $subscribedAlienwareEvents;
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
     * Determines whether or not this user is a "minor" (under 13)
     *
     * @Assert\False(message="You must be 13 years old to register", groups={"Registration"})
     */
    public function isAMinor()
    {
        // if we don't know, we don't know, sooooo not a minor
        if (!$this->getBirthdate()) {
            return false;
        }

        return ($this->getAge() < 13);
    }

    /**
     * @return int
     */
    public function getAge()
    {
        if (!$this->getBirthdate()) {
            return 0;
        }

        return $this->getBirthdate()
            ->diff(new \DateTime('now'))
            ->y
        ;
    }

    /**
     * @return boolean
     */
    public function isAvatarChanged()
    {
        return $this->avatarChanged;
    }

    /**
     * Overridden so that this is shorter - the client wanted a shorter URL
     *
     * @return string
     */
    protected function generateToken()
    {
        $token = parent::generateToken();
        $length = 6;

        return substr($token, 0, $length);
    }

    /**
     * @return \Platformd\UserBundle\Entity\datetime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \Platformd\UserBundle\Entity\datetime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return \Platformd\UserBundle\Entity\datetime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \Platformd\UserBundle\Entity\datetime $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    /**
     * @return string
     */
    public function getCevoUserId()
    {
        return $this->cevoUserId;
    }

    /**
     * @param string $cevoUserId
     */
    public function setCevoUserId($cevoUserId)
    {
        $this->cevoUserId = $cevoUserId;
    }

    /**
     * @return string
     */
    public function getCevoAvatarUrl()
    {
        return $this->cevoAvatarUrl;
    }

    /**
     * @param string $cevoAvatarUrl
     */
    public function setCevoAvatarUrl($cevoAvatarUrl)
    {
        $this->cevoAvatarUrl = $cevoAvatarUrl;
    }

    public function getGroupMembershipActions()
    {
        return $this->groupMembershipActions;
    }

    public function setGroupMembershipActions($value)
    {
        $this->groupMembershipActions = $value;
    }

    public function getAccountLink($locale)
    {
        switch ($locale) {
            case 'ja':
                $subdomain = '/japan';
                break;

            case 'zh':
                $subdomain = '/china';
                break;

            case 'es':
                $subdomain = '/latam';
                break;

            default:
                $subdomain = '';
                break;
        }

        return sprintf('http://www.alienwarearena.com%s/member/%d', $subdomain , $this->cevoUserId);
    }

    public function getLoginRecords()
    {
        return $this->loginRecords;
    }
}
