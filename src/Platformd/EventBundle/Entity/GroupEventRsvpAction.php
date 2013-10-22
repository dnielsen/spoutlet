<?php

namespace Platformd\EventBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Gedmo\Mapping\Annotation as Gedmo;

use DateTime,
    DateTimeZone
;

/**
 * Platformd\EventBundle\Entity\GroupEventRsvpAction
 *
 * @ORM\Table(name="group_event_rsvp_actions")
 * @ORM\Entity(repositoryClass="Platformd\EventBundle\Repository\GroupEventRsvpActionRepository")
 */
class GroupEventRsvpAction extends EventRsvpAction
{
    /**
     * @var integer $id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\EventBundle\Entity\GroupEvent", inversedBy="rsvpActions")
     */
    protected $event;

    public function getEvent()
    {
        return $this->event;
    }

    public function setEvent($value)
    {
        $this->event = $value;
    }

}
