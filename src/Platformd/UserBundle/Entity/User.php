<?php

namespace Platformd\UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use EWZ\Bundle\RecaptchaBundle\Validator\Constraints as Recaptcha;
use FOS\UserBundle\Validator\Password;

use  Platformd\UserBundle\Validator\User as ValidateUser;

/**
 * Platformd\UserBundle\Entity\User
 *
 * @ORM\Table(name="fos_user", indexes={@ORM\index(name="uuid_idx", columns={"uuid"}), @ORM\index(name="cevo_user_id_idx", columns={"cevoUserId"})})
 * @ORM\Entity(repositoryClass="Platformd\UserBundle\Entity\UserRepository")
 * @ORM\haslifecyclecallbacks
 * @ValidateUser()
 * @UniqueEntity(fields={"username"}, message="fos_user.username.already_used")
 * @UniqueEntity(fields={"email"}, message="fos_user.email.already_used")
 */
class User extends BaseUser
{
    public $currentPassword;
    public $sessionUuid;
    public $sessionExpires;

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Assert\NotBlank(message="fos_user.username.blank", groups={"Registration", "Default", "AdminEdit"});
     * @ORM\Column(type="string", length="255", nullable=true)
     */
    protected $username;

    /**
     * @ORM\Column(name="username_canonical", type="string", length="255", nullable=true, unique=true)
     */
    protected $usernameCanonical;

    /**
     * @Assert\NotBlank(message="fos_user.email.blank", groups={"Registration", "Default", "AdminEdit"});
     * @ORM\Column(type="string", length="255")
     */
    protected $email;

    /**
     * @ORM\Column(name="email_canonical", type="string", length="255", unique=true)
     */
    protected $emailCanonical;

    /**
     * @ORM\Column(name="enabled", type="boolean")
     */
    protected $enabled;

    /**
     * @ORM\Column(name="salt", type="string")
     */
    protected $salt;

    /**
     * @ORM\Column(name="password", type="string")
     */
    protected $password;

    /**
     * Plain password. Used for model validation. Must not be persisted.
     * @Assert\MinLength(limit="5", groups={"Registration", "Default"}, message="password_too_short")
     * @Assert\NotBlank(message="fos_user.password.blank", groups={"Registration", "Default"})
     */
    protected $plainPassword;

    /**
     * @ORM\Column(name="last_login", type="datetime", nullable=true)
     */
    protected $lastLogin;

    /**
     * @ORM\Column(name="locked", type="boolean")
     */
    protected $locked;

    /**
     * @ORM\Column(name="expired", type="boolean")
     */
    protected $expired;

    /**
     * @ORM\Column(name="expires_at", type="datetime", nullable=true)
     */
    protected $expiresAt;

    /**
     * @ORM\Column(name="confirmation_token", type="string", nullable=true)
     */
    protected $confirmationToken;

    /**
     * @ORM\Column(name="password_requested_at", type="datetime", nullable=true)
     */
    protected $passwordRequestedAt;

    /**
     * @ORM\Column(name="roles", type="array")
     */
    protected $roles;

    /**
     * @ORM\Column(name="credentials_expired", type="boolean")
     */
    protected $credentialsExpired;

    /**
     * @ORM\Column(name="credentials_expire_at", type="datetime", nullable=true)
     */
    protected $credentialsExpireAt;

    /**
     * @var String $firstname
     *
     * @ORM\Column(type="string", length="255", nullable=true)
     *
     * //@Assert\NotBlank(groups={"Registration", "IncompleteUser", "Default", "AdminEdit"}, message="first_name_not_blank")
     * //@Assert\MinLength(limit="1", groups={"Registration", "IncompleteUser", "AdminEdit"})
     * //@Assert\MaxLength(limit="255", groups={"Registration", "IncompleteUser", "AdminEdit"})
     */
    protected $firstname;

    /**
     * @var String $lastname
     *
     * @ORM\Column(type="string", length="255", nullable=true)
     *
     * //@Assert\NotBlank(groups={"Registration", "IncompleteUser", "Default", "AdminEdit"}, message="last_name_not_blank")
     * //@Assert\MinLength(limit="1", groups={"Registration", "IncompleteUser", "AdminEdit"})
     * //@Assert\MaxLength(limit="255", groups={"Registration", "IncompleteUser", "AdminEdit"})
     */
    protected $lastname;

    /**
     * @var Datetime $birthdate
     *
     * @ORM\Column(type="date", nullable=true)
     *
     * //@Assert\Date(groups={"Registration", "IncompleteUser", "Default", "AdminEdit"})
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
     * //@Assert\NotBlank(groups={"Registration", "Default", "AdminEdit"}, message="country_not_blank")
     */
    protected $country;

    /**
     * @var String $state
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * //@Assert\NotBlank(groups={"Registration", "Default", "AdminEdit"}, message="state_not_blank")
     */
    protected $state;

    /**
     * @var boolean $has_alienware_system
     *
     * @ORM\Column(name="has_alienware_system", type="boolean", nullable=true)
     * //Assert\NotNull(groups={"Registration"}, message="has_system_not_blank")
     */
    protected $hasAlienwareSystem;

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
    protected $subscribedGamingNews = false;

    /**
     * This is the checkbox for:
     *     Subscribe me to receive news on the latest giveaways, contests,
     *     events and more from Alienware Arena via email.
     *
     * @var bool
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $subscribedAlienwareEvents = false;

    /**
     * @var Boolean $terms_accepted
     *
     * //Assert\True(message="You must accept the terms and conditions and the privacy policy", groups={"Registration", "IncompleteUser", "Default"})
     */
    protected $termsAccepted;

    /**
     * @var String $avatar
     *
     * @ORM\OneToOne(targetEntity="Platformd\UserBundle\Entity\Avatar")
     * @ORM\JoinColumn(onDelete="SET NULL")
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
     * @ORM\OneToMany(targetEntity="Platformd\GiveawayBundle\Entity\CodeAssignmentCode", mappedBy="user")
     */
    protected $codeAssignmentCodes;

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
     * The id for this user in CEVO's database
     *
     * @var string
     * @ORM\Column(type="integer", nullable=true)
     */
    private $cevoUserId;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Platformd\GroupBundle\Entity\Group", inversedBy="members")
     * @ORM\JoinTable(name="pd_groups_members")
     */
    private $pdGroups;

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

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $ipAddress;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\UserBundle\Entity\Avatar", mappedBy="user", cascade={"persist"}, orphanRemoval=true)
     */
    protected $avatars;

    /**
     * @ORM\Column(type="string", length=36, nullable=true)
     */
    protected $uuid;

    /**
     * @Recaptcha\True
     */
    public $recaptcha;

    /**
     * @ORM\Column(name="facebook_id", type="string")
     */
    protected $facebookId = '';

    /**
     * @ORM\Column(name="twitter_id", type="string")
     */
    protected $twitterId = '';

    /**
     * @ORM\Column(name="api_successful_login", type="datetime", nullable=true)
     */
    protected $apiSuccessfulLogin;

    /**
     * @ORM\Column(name="about_me", type="text", nullable=true)
     */
    protected $aboutMe;

    /**
     * @ORM\OneToOne(targetEntity="Platformd\UserBundle\Entity\RegistrationSource", cascade={"persist"}, mappedBy="user")
     */
    protected $registrationSource;

    /**
     * @ORM\Column(type="string", length="255", nullable=true)
     * @Assert\NotBlank(message="Please enter your name.", groups={"Registration", "Profile"})
     */
    protected $name = null;

    /**
     * @ORM\Column(type="string", length="255", nullable=true)
     */
    protected $organization = null;

    /**
     * @ORM\Column(type="string", length="255", nullable=true)
     */
    protected $title = null;

    /**
     * @ORM\Column(type="string", length="255", nullable=true)
     */
    protected $industry = null;

    /**
     * @ORM\Column(type="string", length="255", nullable=true)
     */
    protected $affiliation = null;

    /**
     * @ORM\Column(type="string", length="255", nullable=true)
     */
    protected $eventRole = null;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\IdeaBundle\Entity\Idea", mappedBy="creator")
     */
    protected $ideas;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\IdeaBundle\Entity\Comment", mappedBy="user")
     */
    protected $comments;

    public function __construct()
    {
        parent::__construct();
        $this->events                   = new ArrayCollection();
        $this->giveawayKeys             = new ArrayCollection();
        $this->groupMembershipActions   = new ArrayCollection();
        $this->loginRecords             = new ArrayCollection();
        $this->pdGroups                 = new ArrayCollection();
        $this->avatars                  = new ArrayCollection();
        $this->ideas                    = new ArrayCollection();
        $this->comments                 = new ArrayCollection();
    }

    public function __toString() {
        return 'User => { Id = '.$this->getId().', Name = "'.$this->getUsername().'", Age = '.$this->getAge().', IsSuperAdmin = '.($this->getIsSuperAdmin() ? 'True' : 'False').' }';
    }

    /**
     * Return true if the user's avatar has been approved
     *
     * @return Boolean
     */
    public function isAvatarApproved()
    {
        return $this->avatar->isApproved();
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
        if (!$this->avatar || !$this->avatar->isUsable()) {
            return false;
        }

        return $this->avatar;
    }

    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;
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

    public function getIpAddress()
    {

        return $this->ipAddress;
    }

    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;
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
        } elseif ($role == 'ROLE_JAPAN_ADMIN') {
            $this->setIsOrganizer(false);
            $this->setIsSuperAdmin(false);
            $this->switchRole('ROLE_JAPAN_ADMIN', true);
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
        if ($this->hasRole('ROLE_SUPER_ADMIN')) {
            return 'ROLE_SUPER_ADMIN';
        } elseif ($this->hasRole('ROLE_ORGANIZER')) {
            return 'ROLE_ORGANIZER';
        } elseif ($this->hasRole('ROLE_PARTNER')) {
            return 'ROLE_PARTNER';
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getAdminLevelString()
    {
        if ($this->hasRole('ROLE_SUPER_ADMIN')) {
            return 'Full Admin';
        } elseif ($this->hasRole('ROLE_ORGANIZER')) {
            return 'Limited admin';
        } elseif ($this->hasRole('ROLE_PARTNER')) {
            return 'Dell Partner';
        } elseif ($this->hasRole('ROLE_JAPAN_ADMIN')) {
            return 'Japan Regional Admin';
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

    public function getPdGroups()
    {
        return $this->pdGroups;
    }

    public function setPdGroups($pdGroups)
    {
        $this->pdGroups = $pdGroups;
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

    public function getAvatars()
    {
        return $this->avatars;
    }

    public function setAvatars($value)
    {
        $this->avatars = $value;
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function setUuid($value)
    {
        $this->uuid = $value;
    }

    public function eraseCredentials()
    {
    }

    public function hasSameIpAddress($ipAddress)
    {
        return $this->ipAddress === $ipAddress;
    }

    public function getExpired()
    {
        return $this->expired;
    }

    public function setExpired($value)
    {
        $this->expired = $value;
    }

    public function getExpiredUntil()
    {
        return $this->expiresAt;
    }

    public function setExpiredUntil(\DateTime $dateTime = null)
    {
        $this->expiresAt = $dateTime;
    }

    public function isExpired()
    {
        if (true === $this->expired) {
            return true;
        }

        if (null !== $this->expiresAt && $this->expiresAt->getTimestamp() > time()) {
            return true;
        }

        return false;
    }

    public function getFacebookId()
    {
        return $this->facebookId;
    }

    public function setFacebookId($value)
    {
        $this->facebookId = $value;
    }

    /**
     * @param Array
     *
     * Always set the facebook id and facebook role; if there is no first/last name, email or birthdate, set those from fb data
     */
    public function setFBData($fbdata)
    {
        if (isset($fbdata['id'])) {
            $this->setFacebookId($fbdata['id']);
            $this->addRole('ROLE_FACEBOOK');
        }
        if (isset($fbdata['first_name']) && !$this->firstname) {
            $this->setFirstname($fbdata['first_name']);
        }
        if (isset($fbdata['last_name']) && !$this->lastname) {
            $this->setLastname($fbdata['last_name']);
        }
        if (isset($fbdata['email']) && !$this->email) {
            $this->setEmail($fbdata['email']);
        }
        if (isset($fbdata['birthday']) && !$this->birthdate) {
            $this->setBirthdate(new \DateTime($fbdata['birthday']));
        }
    }

    public function getTwitterId()
    {
        return $this->twitterId;
    }

    public function setTwitterId($value)
    {
        $this->twitterId = $value;
    }

    public function getApiSuccessfulLogin()
    {
        return $this->apiSuccessfulLogin;
    }

    public function setApiSuccessfulLogin($value)
    {
        $this->apiSuccessfulLogin = $value;
    }

    public function getAboutMe()
    {
        return $this->aboutMe;
    }

    public function setAboutMe($value)
    {
        $this->aboutMe = $value;
    }

    public function isAccountNonExpired()
    {
        if (true === $this->expired) {
            return false;
        }

        if (null !== $this->expiresAt && $this->expiresAt->getTimestamp() > time()) {
            return false;
        }

        return true;
    }

    public function getRegistrationSource()
    {
        return $this->registrationSource;
    }

    public function setRegistrationSource($value)
    {
        $this->registrationSource = $value;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
    public function getName()
    {
        return $this->name;
    }

    public function setOrganization($value)
    {
        $this->organization = $value;
    }
    public function getOrganization()
    {
        return $this->organization;
    }

    public function setTitle($value)
    {
        $this->title = $value;
    }
    public function getTitle()
    {
        return $this->title;
    }

    public function setIndustry($value)
    {
        $this->industry = $value;
    }
    public function getIndustry()
    {
        return $this->industry;
    }

    public function setAffiliation($affiliation)
    {
        $this->affiliation = $affiliation;
    }
    public function getAffiliation()
    {
        return $this->affiliation;
    }

    public function setEventRole($eventRole)
    {
        $this->$eventRole = $eventRole;
    }
    public function getEventRole()
    {
        return $this->eventRole;
    }


    public function addComment($comments)
    {
        $this->comments[] = $comments;
    }
    public function getComments()
    {
        return $this->comments;
    }

    public function addIdea($idea)
    {
        $this->ideas[] = $idea;
    }
    public function removeIdea($idea)
    {
        $this->ideas->removeElement($idea);
    }
    public function getIdeas()
    {
        return $this->ideas;
    }

    public function getCodeAssignmentCodes()
    {
        return $this->codeAssignmentCodes;
    }

    public function setCodeAssignmentCodes($value)
    {
        $this->codeAssignmentCodes = $value;
    }
}
