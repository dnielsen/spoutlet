<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use DateTime;
use DateTimeZone;

use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;

class GroupDiscussionPostRepository extends EntityRepository
{
    public function getDiscussionPostsMostRecentFirst($discussion, $maxPerPage = 10, $currentPage = 1)
    {
        $qb = $this->createQueryBuilder('gdp')
            ->where('gdp.groupDiscussion = :discussionId')
            ->andWhere('gdp.deleted = false')
            ->setParameter('discussionId', $discussion->getId())
            ->orderBy('gdp.created', 'DESC')
        ;

        $adapter = new DoctrineORMAdapter($qb);
        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage($maxPerPage)->setCurrentPage($currentPage);

        return $pager;
    }

    public function getDiscussionPosts($discussion)
    {
        $qb = $this->createQueryBuilder('gdp')
            ->where('gdp.groupDiscussion = :discussionId')
            ->andWhere('gdp.deleted = false')
            ->setParameter('discussionId', $discussion->getId())
            ->orderBy('gdp.created', 'DESC')
        ;

        return $qb->getQuery()->execute();
    }
}
