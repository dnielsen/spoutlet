<?php

namespace Platformd\EventBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Gedmo\Mapping\Annotation as Gedmo;

use DateTime,
    DateTimeZone
;

/**
 * Platformd\EventBundle\Entity\GlobalEventRsvpAction
 *
 * @ORM\Table(name="global_event_rsvp_actions")
 * @ORM\Entity
 */
class GlobalEventRsvpAction extends EventRsvpAction
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
     * @ORM\ManyToOne(targetEntity="Platformd\EventBundle\Entity\GlobalEvent", inversedBy="rsvpActions")
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
