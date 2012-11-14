<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * ContestEntryRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ContestEntryRepository extends EntityRepository
{
    public function findOneByUserAndContest($user, $contest)
    {
        return $this->createQueryBuilder('ce')
            ->where('ce.user = :user')
            ->andWhere('ce.contest = :contest')
            ->setParameter('user', $user)
            ->setParameter('contest', $contest)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllNotDeletedForContest($contest)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.contest = :contest')
            ->andWhere('e.deleted <> 1')
            ->setParameter('contest', $contest)
            ->getQuery()
            ->execute();
    }
}
