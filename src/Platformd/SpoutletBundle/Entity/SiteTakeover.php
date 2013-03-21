<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Platformd\SpoutletBundle\Util\TimeZoneUtil as TzUtil;
use Symfony\Component\Validator\ExecutionContext;
use DateTime;
use DateTimezone;

/**
 * Platformd\SpoutletBundle\Entity\SiteTakeover
 *
 * @ORM\Table(name="site_takeover")
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\SiteTakeoverRepository")
 * @Assert\Callback(methods={"validateDateRanges"})
 */
class SiteTakeover
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Assert\NotNull
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @ORM\ManyToMany(targetEntity="Platformd\SpoutletBundle\Entity\Site")
     * @ORM\JoinTable(name="site_takeover_site")
     */
    private $sites;

    /**
     * @ORM\Column(name="starts_at", type="datetime")
     */
    private $startsAt;

    /**
     * @ORM\Column(name="ends_at", type="datetime")
     */
    private $endsAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $timezone = 'UTC';

    /**
     * @ORM\Column(name="content", type="text")
     */
    private $content;

    public function __construct()
    {
        $this->sites = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setSites($sites)
    {
        $this->sites = $sites;
    }

    public function getSites()
    {
        return $this->sites;
    }

    public function setStartsAt($startsAt)
    {
        $this->startsAt = $startsAt;
    }

    public function getStartsAt()
    {
        return $this->startsAt;
    }

    public function setEndsAt($endsAt)
    {
        $this->endsAt = $endsAt;
    }

    public function getEndsAt()
    {
        return $this->endsAt;
    }

    public function getStartsAtUtc()
    {
        if (!$this->getStartsAt()) {
            return null;
        }

        return TzUtil::getUtc($this->getStartsAt(), new \DateTimeZone($this->getTimezone()));
    }

    public function getEndsAtUtc()
    {
        if (!$this->getEndsAt()) {
            return null;
        }

        return TzUtil::getUtc($this->getEndsAt(), new \DateTimeZone($this->getTimezone()));
    }

    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
    }

    public function getTimezone()
    {
        return $this->timezone ? $this->timezone : 'UTC';
    }

    public function getDateTimeUtc(DateTime $datetime)
    {
        return TzUtil::getUtc($datetime, new \DateTimeZone($this->getTimezone()));
    }

    private function convertDatetimeToTimezone(DateTime $dt)
    {
        $userTimezone = new DateTimeZone($this->getTimezone());
        $offset = $userTimezone->getOffset($dt);

        $timestamp = $dt->format('U') + $offset;

        return DateTime::createFromFormat('U', $timestamp, $userTimezone);
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getSiteList()
    {
        $siteList = '';
        foreach ($this->getSites() as $site) {
            $siteList .=  '['.$site->getName().']';
        }

        return $siteList;
    }

    public function validateDateRanges(ExecutionContext $executionContext)
    {
        // error if submissionEnd or votingEnd datetime values are before their respective start datetimes

        if ($this->endsAt > $this->startsAt) {
            return;
        }

        if ($this->endsAt < $this->startsAt) {
            $propertyPath = $executionContext->getPropertyPath() . '.endsAt';
            $executionContext->setPropertyPath($propertyPath);

            $executionContext->addViolation(
                "The  end date/time must be after the start date/time",
                array(),
                "endsAt"
            );

            return;
        }
    }
}
