<?php

namespace Platformd\EventBundle\Repository;

use Doctrine\ORM\EntityManager,
    Doctrine\ORM\EntityRepository,
    Platformd\EventBundle\Entity\EventRsvpAction
;

class GroupEventRsvpActionRepository extends EntityRepository
{
    public function getUserApprovedStatus($event, $user)
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r.attendance')
            ->andWhere('r.event = :event')
            ->andWhere('r.user = :user')
            ->setParameter('event', $event)
            ->setParameter('user', $user);

        $queryResult = $qb->getQuery()->getOneOrNullResult();

        if(!$queryResult){
            return null;
        }

        $attendanceStatus = reset($queryResult);

        if ($attendanceStatus == EventRsvpAction::ATTENDING_PENDING) {
            return 'pending';
        }
        elseif ($attendanceStatus == EventRsvpAction::ATTENDING_REJECTED) {
            return 'rejected';
        }
        else {
            return 'approved';
        }
    }

}
