<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Platformd\MediaBundle\Entity\Media;
use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Symfony\Component\Validator\ExecutionContext;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use DateTime;
use DateTimezone;

/**
 * Platformd\SpoutletBundle\Entity\Deal
 * @ORM\Table(
 *      name="pd_deal",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="slug_unique",
 *              columns={"slug"}
 *          )
 *      }
 * )
 * @UniqueEntity(fields={"slug"}, message="This URL is already used.  If you have left slug blank, this means that an existing deal is already using this deal name.")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\DealRepository")
 */

class Deal implements LinkableInterface
{

    /**
     * A map of UTC offsets and common timezone names
     *
     * This is because all we have are things like "Tokyo", but we may want
     * to actually say JST
     *
     * @var array
     */
    static private $timzoneCommonNames = array(
        32400 => 'JST',
    );

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

     /**
     * @var string $slug
     *
     * @ORM\Column(name="slug", type="string", length=255)
     */
    private $slug;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotNull
     */
    private $name;

    /**
     * @Assert\Url
     * @var string
     * @ORM\Column(name="external_url", length="255", nullable=true)
     */
    private $externalUrl;

     /**
     * @var \Platformd\SpoutletBundle\Entity\Game
     * @ORM\ManyToOne(targetEntity="Platformd\SpoutletBundle\Entity\Game")
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     */
    private $game;

    /**
     * @var \DateTime $startsAt
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $startsAt;

    /**
     * @var \DateTime $endsAt
     * @ORM\Column(name="ends_at", type="datetime")
     */
    private $endsAt;

    /**
     * The timezone this event is taking place in
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @var string
     */
    protected $timezone = 'UTC';

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
     * Set slug
     *
     * @param string $slug
     */
    public function setSlug($slug)
    {
        # this allows slug to be left blank and set elsewhere without it getting overridden here
        if (!$slug) {
            return;
        }

        $this->slug = $slug;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        if (!$this->getSlug()) {
            $slug = Urlizer::urlize($name);

            $this->setSlug($slug);
        }

        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

     /**
     * @param string $externalUrl
     */
    public function setExternalUrl($externalUrl) {
        $this->externalUrl = $externalUrl;
    }

    /**
     * @return string
     */
    public function getExternalUrl() {
        return $this->externalUrl;
    }

     /**
     * @return \Platformd\SpoutletBundle\Entity\Game
     */
    public function getGame()
    {
        return $this->game;
    }

    /**
     * @param \Platformd\SpoutletBundle\Entity\Game $game
     */
    public function setGame(Game $game)
    {
        $this->game = $game;
    }

    /**
     * @return \DateTime
     */
    public function getStartsAt()
    {
        return $this->startsAt;
    }

    /**
     * @param \DateTime $startsAt
     */
    public function setStartsAt(\DateTime $startsAt)
    {
        $this->startsAt = $startsAt;
    }

    /**
     * @return \DateTime
     */
    public function getEndsAt()
    {
        return $this->endsAt;
    }

    /**
     * @param \DateTime $startsAt
     */
    public function setEndsAt(\DateTime $endsAt)
    {
        $this->endsAt = $endsAt;
    }

    /**
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone ? $this->timezone : 'UTC';
    }

    /**
     * Tries to get a friendly name for the event's timezone
     *
     * @return string
     */
    public function getTimezoneString()
    {
        $dtz = new \DateTimeZone($this->getTimezone());

        $offset = $dtz->getOffset(new DateTime());

        return isset(self::$timzoneCommonNames[$offset]) ? self::$timzoneCommonNames[$offset] : $dtz->getName();
    }

    /**
     * @param string $timezone
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
    }

    private function convertDatetimeToTimezone(DateTime $dt)
    {
        $userTimezone = new DateTimeZone($this->getTimezone());
        $offset = $userTimezone->getOffset($dt);

        $timestamp = $dt->format('U') + $offset;

        return DateTime::createFromFormat('U', $timestamp, $userTimezone);
    }

     /**
     * If there is a set URL that should be used without doing anything else, return it here
     *
     * @return string
     */
    public function getLinkableOverrideUrl()
    {
        return $this->getExternalUrl();
    }

    /**
     * Returns the name of the route used to link to this object
     *
     * @return string
     */
    public function getLinkableRouteName()
    {
        return 'deal_show';
    }

    /**
     * Returns an array route parameters to link to this object
     *
     * @return array
     */
    public function getLinkableRouteParameters()
    {
        return array(
            'slug' => $this->getSlug()
        );
    }
}
