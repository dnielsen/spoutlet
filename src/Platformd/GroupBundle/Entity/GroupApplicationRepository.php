<?php

namespace Platformd\GroupBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use DateTime;
use DateTimeZone;

/**
 * GroupApplicationRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class GroupApplicationRepository extends EntityRepository
{
    public function getApplicationsForGroup($group) {
         return $this->getEntityManager()->createQuery('
            SELECT app FROM GroupBundle:GroupApplication app
            LEFT JOIN app.group group
            LEFT JOIN app.applicant user
            WHERE app.group = :group
            ORDER BY app.createdAt')
            ->setParameter('group', $group)
            ->execute();
    }

    public function isUserApplicantToGroup($user, $group)
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id) as isApplicant')
            ->andWhere('a.applicant = :user')
            ->andWhere('a.group = :group')
            ->setParameter('user', $user)
            ->setParameter('group', $group)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
