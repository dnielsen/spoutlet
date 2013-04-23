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

        $qb->join('s.user', 'u');
        $qb->join('s.giveaway', 'g');
        $qb->andWhere('u.id = :userId');
        $qb->andWhere('g.id = :giveawayId');

        $qb->setParameter('userId', $userId);
        $qb->setParameter('giveawayId', $giveawayId);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
