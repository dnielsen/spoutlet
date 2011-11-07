<?php

namespace Platformd\SpoutletBundle\Entity;

use Platformd\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection,
    Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Platformd\SpoutletBundle\Entity\Event
 *
 * @ORM\Entity(repositoryClass="Platformd\SpoutletBundle\Entity\GiveawayRepository")
 */
class Giveaway extends AbstractEvent
{

}