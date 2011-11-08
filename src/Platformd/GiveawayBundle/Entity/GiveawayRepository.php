<?php

namespace Platformd\GiveawayBundle\Entity;

use Platformd\SpoutletBundle\Entity\AbstractEventRepository;

/**
 * Giveaway Repository
 */
class GiveawayRepository extends AbstractEventRepository
{
    public function findActives()
    {
        
        return $this->findAll();
    }
}
