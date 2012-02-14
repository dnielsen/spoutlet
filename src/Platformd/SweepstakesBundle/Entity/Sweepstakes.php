<?php

namespace Platformd\SweepstakesBundle\Entity;

use Platformd\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection,
    Doctrine\Common\Collections\ArrayCollection;
use Platformd\SpoutletBundle\Entity\AbstractEvent;
use Gedmo\Mapping\Annotation as Gedmo;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Platformd\SweepstakesBundle\Entity\Sweepstakes
 *
 * @ORM\Entity(repositoryClass="Platformd\SweepstakesBundle\Entity\SweepstakesRepository")
 */
class Sweepstakes extends AbstractEvent
{
}