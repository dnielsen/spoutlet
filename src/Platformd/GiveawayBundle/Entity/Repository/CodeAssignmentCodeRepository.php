<?php

namespace Platformd\GiveawayBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Platformd\UserBundle\Entity\User;

class CodeAssignmentCodeRepository extends EntityRepository
{
    public function getUnsentCodeQuery()
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.emailSentAt IS NULL')
            ->getQuery();

        return $qb;
    }

    public function getUnsentCodeCount()
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.emailSentAt IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getCodesAssignedForUser(User $user)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->andWhere('c.emailSentAt IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}
