<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use DateTime;
use DateTimeZone;

class GroupMembershipActionRepository extends EntityRepository
{
    public function getMembersLeftCountByGroup($group, $fromDate=null, $thruDate=null)
    {
        $qb = $this->createQueryBuilder('gma');
        $qb->where('gma.group = :group');
        $qb->andWhere('gma.action = :action');
        $qb->setParameter('group', $group);
        $qb->setParameter('action', 'LEFT');

        if($fromDate != null and $thruDate != null)
        {
            $qb->andWhere('gma.createdAt >= :fromDate')
               ->andWhere('gma.createdAt <= :thruDate')
               ->setParameter('fromDate', $fromDate)
               ->setParameter('thruDate', $thruDate);
        }

        $total = $qb->getQuery()->getResult();

        return count($total);
    }
}
