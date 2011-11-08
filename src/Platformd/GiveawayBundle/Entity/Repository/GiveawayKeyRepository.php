<?php

namespace Platformd\GiveawayBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Platformd\GiveawayBundle\Entity\GiveawayPool;

/**
 * GiveawayKey  Repository
 */
class GiveawayKeyRepository extends EntityRepository
{
    /**
     * Returns the number of keys that have been assigned for the given pool
     *
     * @todo
     * @param \Platformd\GiveawayBundle\Entity\GiveawayPool $pool
     * @return int
     */
    public function getAssignedForPool(GiveawayPool $pool)
    {
        return 0;
    }

    /**
     * Returns the total number of keys for the given pool
     *
     * @todo
     * @param \Platformd\GiveawayBundle\Entity\GiveawayPool $pool
     * @return int
     */
    public function getTotalForPool(GiveawayPool $pool)
    {
        return 10000;
    }
}
