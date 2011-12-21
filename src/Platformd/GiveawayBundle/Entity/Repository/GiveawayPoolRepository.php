<?php

namespace Platformd\GiveawayBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Platformd\GiveawayBundle\Entity\Giveaway;
use Doctrine\ORM\QueryBuilder;

/**
 * GiveawayKey  Repository
 */
class GiveawayPoolRepository extends EntityRepository
{
    /**
     * @param \Platformd\GiveawayBundle\Entity\Giveaway $giveaway
     * @return \Platformd\GiveawayBundle\Entity\GiveawayPool[]
     */
    public function findPoolsForGiveaway(Giveaway $giveaway)
    {
        return $this->createQueryBuilder('gp')
            ->andWhere('gp.giveaway = :giveaway')
            ->setParameter('giveaway', $giveaway)
            ->getQuery()
            ->execute()
        ;
    }
}