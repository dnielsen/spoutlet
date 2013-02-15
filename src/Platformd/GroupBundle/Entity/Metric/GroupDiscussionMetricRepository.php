<?php

namespace Platformd\GroupBundle\Entity\Metric;

use DateTime,
    DateTimeZone
;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Platformd\GroupBundle\Entity\GroupDiscussion;

class GroupDiscussionMetricRepository extends EntityRepository
{
    public function save(GroupDiscussionMetric $groupDiscussionMetric)
    {
        $this->_em->persist($groupDiscussionMetric);
        $this->_em->flush();
    }

    public function deleteAllFromGroupDiscussion(GroupDiscussion $groupDiscussion)
    {
        $qb = $this->createQueryBuilder('gDM');
        $qb
            ->delete()
            ->where('gDM.groupDiscussion = :groupDiscussion')
            ->setParameter('groupDiscussion', $groupDiscussion)
        ;

        $qb->getQuery()->execute();
    }

    /**
     * Retrieves not fully processed group metrics
     *
     * @param \Platformd\GroupBundle\Entity\Group $group
     * @return array
     */
    public function findIncompleteMetricsForGroupDiscussion(GroupDiscussion $groupDiscussion)
    {
        $qb = $this->createQueryBuilder('gDM')
            ->where('gDM.groupDiscussion = :groupDiscussion')
            ->andWhere('DATE_DIFF(gDM.updated, gDM.date) < 1')
            ->setParameter('groupDiscussion', $groupDiscussion)
        ;

        return $qb->getQuery()->getResult();
    }


    public function findLastMetricForGroup(GroupDiscussion $groupDiscussion)
    {
        $qb = $this->createQueryBuilder('gDM')
            ->where('gDM.groupDiscussion = :groupDiscussion')
            ->setParameter('groupDiscussion', $groupDiscussion)
            ->orderBy('gDM.date', 'DESC')
            ->setMaxResults(1)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     *
     *
     * @param \Platformd\GroupBundle\Entity\GroupDiscussion $groupDiscussion
     * @param \DateTime $from
     * @param \DateTime $thru
     * @return array
     */
    public function findMetricsForPeriod(GroupDiscussion $groupDiscussion, DateTime $from = null, DateTime $thru = null)
    {
        $qb = $this->createQueryBuilder('gDM')
            ->where('gDM.groupDiscussion = :groupDiscussion')
            ->setParameter('groupDiscussion', $groupDiscussion)
            ->orderBy('gDM.date', 'ASC')
        ;

        if ($from !== null) {
            $qb
                ->andWhere('gDM.date >= :from')
                ->setParameter('from', $from);
        }

        if ($thru !== null) {
            $qb
                ->andWhere('gDM.date <= :thru')
                ->setParameter('thru', $thru);
        }

        return $qb->getQuery()->getResult();
    }
}
