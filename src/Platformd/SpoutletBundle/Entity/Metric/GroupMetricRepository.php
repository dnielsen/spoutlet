<?php

namespace Platformd\SpoutletBundle\Entity\Metric;

use DateTime,
    DateTimeZone
;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Platformd\SpoutletBundle\Entity\Group;

class GroupMetricRepository extends EntityRepository
{
    public function save(GroupMetric $groupMetric)
    {
        $this->_em->persist($groupMetric);
        $this->_em->flush();
    }

    public function deleteAllFromGroup(Group $group)
    {
        $qb = $this->createQueryBuilder('gM');
        $qb
            ->delete()
            ->where('gM.group = :group')
            ->setParameter('group', $group)
        ;

        $qb->getQuery()->execute();
    }

    /**
     * Retrieves not fully processed group metrics
     *
     * @param \Platformd\SpoutletBundle\Entity\Group $group
     * @return array
     */
    public function findIncompleteMetricsForGroup(Group $group)
    {
        $qb = $this->createQueryBuilder('gM')
            ->where('gM.group = :group')
            ->andWhere('DATE_DIFF(gM.updated, gM.date) < 1')
            ->setParameter('group', $group)
        ;

        return $qb->getQuery()->getResult();
    }

    public function findLastMetricForGroup(Group $group)
    {
        $qb = $this->createQueryBuilder('gM')
            ->where('gM.group = :group')
            ->setParameter('group', $group)
            ->orderBy('gM.date', 'DESC')
            ->setMaxResults(1)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     *
     *
     * @param \DateTime $from
     * @param \DateTime $thru
     */
    public function findMetricsForPeriod(Group $group, DateTime $from, DateTime $thru)
    {
        $qb = $this->createQueryBuilder('gM')
            ->where('gM.group = :group')
            ->andWhere('gM.date >= :from')
            ->andWhere('gM.date <= :thru')
            ->setParameter('group', $group)
            ->setParameter('from', $from)
            ->setParameter('thru', $thru)
            ->orderBy('gM.date', 'ASC')
        ;

        return $qb->getQuery()->getResult();
    }
}
