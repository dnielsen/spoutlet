<?php

namespace Platformd\IdeaBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * VoteCriteriaRepository
 */
class VoteCriteriaRepository extends EntityRepository
{

    public function findByEventId($eventId)
    {
        $qb = $this->createQueryBuilder('vc')
            ->select      ('vc')
            ->where       ('vc.event = :eventId')
            ->setParameter('eventId', $eventId);

        return $qb->getQuery()->getResult();
    }

}

?>

