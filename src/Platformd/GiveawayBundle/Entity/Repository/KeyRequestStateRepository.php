<?php

namespace Platformd\GiveawayBundle\Entity\Repository;

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityRepository;

use DateTime;

class KeyRequestStateRepository extends EntityRepository
{

    public function findForUserIdAndGiveawayId($userId, $giveawayId) {

        $qb = $this->createQueryBuilder('s');

        $query = $qb ->join('s.user', 'u')
            ->join('s.giveaway', 'g')
            ->andWhere('u.id = :userId')
            ->andWhere('g.id = :giveawayId')
            ->setParameter('userId', $userId)
            ->setParameter('giveawayId', $giveawayId)
            ->getQuery();

        return $query->getOneOrNullResult();
    }
}
