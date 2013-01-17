<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use DateTime;

use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;

class GroupDiscussionRepository extends EntityRepository
{
    public function findAllGroupDiscussionsRelevantForSite($site) {

        return $this->getEntityManager()->createQuery('
            SELECT gD FROM SpoutletBundle:GroupDiscussion gD
            LEFT JOIN gD.group g
            LEFT JOIN g.sites s
            WHERE gD.deleted = false
            AND (g.allLocales = true OR s = :site)')
            ->setParameter('site', $site)
            ->execute();
    }

    public function getDiscussionsForGroupMostRecentFirst($group, $maxPerPage = 10, $currentPage = 1)
    {
        $qb = $this->createQueryBuilder('gD')
            ->where('gD.group = :groupId')
            ->andWhere('gD.deleted = false')
            ->setParameter('groupId', $group->getId())
            ->orderBy('gD.createdAt', 'DESC')
        ;

        $adapter = new DoctrineORMAdapter($qb);
        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage($maxPerPage)->setCurrentPage($currentPage);

        return $pager;
    }

    public function findDiscussionStats(array $data = array())
    {
        $filters = array_merge(
            array('discussionName' => '', 'deleted' => '', 'sites' => array(), 'startDate' => '', 'endDate' => ''),
            $data
        );

        $qb = $this->getFindDiscussionsQB($filters['discussionName'], $filters['deleted'], $filters['sites'], $filters['from'], $filters['thru']);

        $sql = $qb->getQuery()->getSQL();

        return $qb->getQuery()->execute();
    }

    public function getFindDiscussionsQB($discussionName, $status, $sites, $from="", $thru="")
    {
        $qb = $this->createQueryBuilder('gD')
            ->leftJoin('gD.group', 'g')
            ->leftJoin('g.sites', 's');

        if (count($sites) > 0) {

            $qb->andWhere('(s.defaultLocale IN (:siteList) OR g.allLocales = true)');
            $qb->setParameter('siteList', $sites);

        }

        if ($discussionName) {
            $qb->andWhere('gD.title like :discussionName');
            $qb->setParameter('discussionName', '%'.$discussionName.'%');
        }

        if ($status != "") {
            $qb->andWhere('gD.deleted = :status');
            $qb->setParameter('status', $status);
        }

        if ($from != "") {

            $from->setTime(0, 0, 0);
            $qb->andWhere('gD.createdAt >= :from');
            $qb->setParameter('from', $from);
        }

        if ($thru != "") {

            $thru->setTime(23, 59, 59);
            $qb->andWhere('gD.createdAt <= :thru');
            $qb->setParameter('thru', $thru);
        }

        $qb->distinct('gD.id');

        return $qb;
    }
}
