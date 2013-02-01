<?php

namespace Platformd\SpoutletBundle\Model;

use Doctrine\ORM\EntityManager;

use Platformd\SpoutletBundle\Entity\Superclass\Activity;

/**
 * Manager for Activities:
 *
 * Persists activities
 *
 *
 */
class ActivityManager
{
    /* @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function save(Activity $activity)
    {
        $this->em->persist($activity);
        $this->em->flush();
    }
}
