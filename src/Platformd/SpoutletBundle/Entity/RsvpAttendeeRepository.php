<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;

class RsvpAttendeeRepository extends EntityRepository
{
    public function findAssignedCodesWithValue($value)
    {
        $qb = $this->createQueryBuilder('a')
            ->select('c.id')
            ->leftJoin('a.code', 'c')
            ->andWhere('c.value = :value')
            ->setParameter('value', $value);

        $result = $qb->getQuery()
            ->getResult();

        $return = array();

        foreach ($result as $code) {
            $return[] = $code['id'];
        }

        return $return;
    }
}
