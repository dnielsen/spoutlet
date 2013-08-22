<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;
use DateTime;

class MassEmailRepository extends EntityRepository
{
    public function getRecentEmailCountForUser($user)
    {
        $timePeriod = MassEmail::EMAIL_LIMIT_PERIOD;
        $since      = new DateTime('- '.$timePeriod);

        $count = $this->createQueryBuilder('e')
            ->select('COUNT(e.id) emailCount')
            ->andWhere('e.sender = :user')
            ->andWhere('e.sentAt > :since')
            ->setParameter('user', $user)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        return $count;
    }
}
