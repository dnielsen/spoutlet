<?php

namespace Platformd\SweepstakesBundle\Entity;

use Platformd\SpoutletBundle\Entity\AbstractEventRepository;
use Platformd\SweepstakesBundle\Entity\Sweepstakes;
use Platformd\UserBundle\Entity\User;

/**
 * Repository class for Sweepstakes
 */
class SweepstakesRepository extends AbstractEventRepository
{
    public function createNewEntry(Sweepstakes $sweepstakes, User $user, $ipAddress)
    {
        $entry = new Entry();

        $entry->setSweepstakes($sweepstakes);
        $entry->setUser($user);
        $entry->setIpAddress($ipAddress);

        return $entry;
    }
}