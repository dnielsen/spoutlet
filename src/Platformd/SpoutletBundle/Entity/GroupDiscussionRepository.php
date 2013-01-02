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
            WHERE g.deleted = false
            AND (g.allLocales = true OR s = :site)')
            ->setParameter('site', $site)
            ->execute();
    }

    public function getDiscussionsForGroupMostRecentFirst($group, $maxPerPage = 10, $currentPage = 1)
    {
        $qb = $this->createQueryBuilder('gd')
            ->where('gd.group = :groupId')
            ->andWhere('gd.deleted = false')
            ->setParameter('groupId', $group->getId())
            ->orderBy('gd.created', 'DESC')
        ;

        $adapter = new DoctrineORMAdapter($qb);
        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage($maxPerPage)->setCurrentPage($currentPage);

        return $pager;
    }
}
