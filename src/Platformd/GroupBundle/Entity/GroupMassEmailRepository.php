<?php

namespace Platformd\GroupBundle\Entity;

use Platformd\SpoutletBundle\Entity\MassEmail;
use Platformd\SpoutletBundle\Entity\MassEmailRepository;

use DateTime;

class GroupMassEmailRepository extends MassEmailRepository
{
    public function hasUserHitEmailLimitForGroup($user, $group)
    {
        $limit      = MassEmail::EMAIL_LIMIT_COUNT;
        $timePeriod = MassEmail::EMAIL_LIMIT_PERIOD;
        $since      = new DateTime('- '.$timePeriod);

        $count = $this->createQueryBuilder('e')
            ->select('COUNT(e.id) emailCount')
            ->andWhere('e.sender = :user')
            ->andWhere('e.group = :group')
            ->andWhere('e.createdAt > :since')
            ->setParameter('group', $group)
            ->setParameter('user', $user)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        return $count >= $limit;
    }
}
