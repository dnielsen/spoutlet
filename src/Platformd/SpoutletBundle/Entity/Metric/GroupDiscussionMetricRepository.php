<?php

namespace Platformd\SpoutletBundle\Entity\Metric;

use DateTime,
    DateTimeZone
;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Platformd\SpoutletBundle\Entity\GroupDiscussion;

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
     * @param \Platformd\SpoutletBundle\Entity\Group $group
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
}
