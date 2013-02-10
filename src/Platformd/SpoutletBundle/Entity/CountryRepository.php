<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;

class CountryRepository extends EntityRepository
{

    public function getUserAssignedCodes(User $user)
    {
        return $this
            ->createQueryBuilder('c')
            ->leftJoin('k.pool','gkp')
            ->andWhere('k.user = :user')
            ->setParameters(array('user' => $user))
            ->getQuery()
            ->execute();
    }
}
