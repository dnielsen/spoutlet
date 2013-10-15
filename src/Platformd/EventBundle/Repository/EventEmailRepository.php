<?php

namespace Platformd\EventBundle\Repository;

use Platformd\SpoutletBundle\Entity\MassEmail;
use Platformd\SpoutletBundle\Entity\MassEmailRepository;

use DateTime;

class EventEmailRepository extends MassEmailRepository
{
    public function hasUserHitEmailLimitForEvent($user, $event)
    {
        $limit      = MassEmail::EMAIL_LIMIT_COUNT;
        $timePeriod = MassEmail::EMAIL_LIMIT_PERIOD;
        $since      = new DateTime('- '.$timePeriod);

        $count = $this->createQueryBuilder('e')
            ->select('COUNT(e.id) emailCount')
            ->andWhere('e.sender = :user')
            ->andWhere('e.event = :event')
            ->andWhere('e.createdAt > :since')
            ->setParameter('event', $event)
            ->setParameter('user', $user)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        return $count >= $limit;
    }
}
