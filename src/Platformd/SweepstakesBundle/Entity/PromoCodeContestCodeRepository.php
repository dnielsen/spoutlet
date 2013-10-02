<?php

namespace Platformd\SweepstakesBundle\Entity;

use Doctrine\ORM\EntityRepository;

class PromoCodeContestCodeRepository extends EntityRepository
{
    public function findCountForSweepstakes($sweepstakes)
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.contest = :sweepstakes')
            ->setParameter('sweepstakes', $sweepstakes)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
