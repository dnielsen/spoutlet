<?php

namespace Platformd\EventBundle\Repository;

use Platformd\GroupBundle\Entity\Group;
use Platformd\EventBundle\Repository\EventRepository;
use Platformd\UserBundle\Entity\User;
use Platformd\SpoutletBundle\Entity\Site;
use DateTime;

use Pagerfanta\Pagerfanta,
    Pagerfanta\Adapter\DoctrineORMAdapter
;

class GroupEventRepository extends EventRepository
{
    public function findUpcomingEventsForGroupMostRecentFirst(Group $group, $limit=null)
    {
        $qb = $this->getBaseGroupQueryBuilder($group)
            ->andWhere('e.endsAt > :now')
            ->andWhere('e.published = 1')
            ->andWhere('e.active = 1')
            ->orderBy('e.startsAt')
            ->setParameter('now', new \DateTime('now'));

        $this->addActiveClauses($qb);

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function findOngoingEventsForGroup(Group $group, $limit=null)
    {
        $qb = $this->getBaseGroupQueryBuilder($group)
            ->andWhere('e.startsAt is NULL')
            ->andWhere('e.active = 1');

        $this->addActiveClauses($qb);

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function findPastEventsForGroupMostRecentFirst(Group $group, $limit=null)
    {
        $qb = $this->getBaseGroupQueryBuilder($group)
            ->andWhere('e.endsAt < :now')
            ->andWhere('e.published = 1')
            ->andWhere('e.active = 1')
            ->orderBy('e.endsAt', 'DESC')
            ->setParameter('now', new \DateTime('now'));

        $this->addActiveClauses($qb);

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    private function getBaseGroupQueryBuilder(Group $group, $alias = 'e')
    {
        $qb = $this->createQueryBuilder($alias)
            ->andWhere($alias.'.group = :group')
            ->setParameter('group', $group);

        return $qb;
    }

    public function getPendingApprovalEventsForGroup(Group $group)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e', 'g')
            ->leftJoin('e.group', 'g')
            ->where('e.approved = false')
            ->andWhere('e.group = :group')
            ->setParameter('group', $group)
            ->orderBy('e.createdAt', 'DESC')
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns all upcoming events, ability to paginate
     *
     * @param \Platformd\SpoutletBundle\Entity\Site $site
     * @param int $maxPerPage
     * @param int $currentPage
     * @param $pager
     * @param bool $published
     * @return array
     */
    public function findUpcomingEventsForSitePaged(Site $site, $maxPerPage = 20, $currentPage = 1, &$pager, $published = true, $private = false)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e', 's')
            ->leftJoin('e.sites', 's')
            ->leftJoin('e.group', 'g')
            ->where('e.endsAt >= :now')
            ->andWhere('e.published = :published')
            ->andWhere('s = :site')
            ->andWhere('e.private = :private')
            ->andWhere('e.deleted <> 1')
            ->orderBy('e.createdAt', 'DESC')
            ->setParameter('now', new DateTime())
            ->setParameter('published', $published)
            ->setParameter('site', $site)
            ->setParameter('private', $private)
        ;

        if ($maxPerPage) {
            $adapter = new DoctrineORMAdapter($qb);
            $pager = new Pagerfanta($adapter);
            $pager->setMaxPerPage($maxPerPage)->setCurrentPage($currentPage);

            return $pager->getCurrentPageResults();
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns all upcoming events, sorted by start time
     *
     * @param \Platformd\SpoutletBundle\Entity\Site $site
     * @param bool $published
     * @return array
     */
    public function findUpcomingEventsForSiteSorted(Site $site,$published = true, $private = false)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e', 's')
            ->leftJoin('e.sites', 's')
            ->leftJoin('e.group', 'g')
            ->where('e.endsAt >= :now')
            ->andWhere('e.published = :published')
            ->andWhere('s = :site')
            ->andWhere('e.private = :private')
            ->andWhere('e.deleted <> 1')
            ->orderBy('e.startsAt', 'ASC')
            ->setParameter('now', new DateTime())
            ->setParameter('published', $published)
            ->setParameter('site', $site)
            ->setParameter('private', $private)
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns all past events, ability to paginate
     *
     * @param \Platformd\SpoutletBundle\Entity\Site $site
     * @param int $maxPerPage
     * @param int $currentPage
     * @param $pager
     * @param bool $published
     * @return array
     */
    public function findPastEventsForSitePaged(Site $site, $maxPerPage = 20, $currentPage = 1, &$pager, $published = true, $private = false)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e', 's')
            ->leftJoin('e.sites', 's')
            ->leftJoin('e.group', 'g')
            ->where('e.endsAt < :now')
            ->andWhere('e.published = :published')
            ->andWhere('s = :site')
            ->andWhere('e.private = :private')
            ->andWhere('e.deleted <> 1')
            ->orderBy('e.createdAt', 'DESC')
            ->setParameter('now', new DateTime())
            ->setParameter('published', $published)
            ->setParameter('site', $site)
            ->setParameter('private', $private)
        ;

        if ($maxPerPage) {
            $adapter = new DoctrineORMAdapter($qb);
            $pager = new Pagerfanta($adapter);
            $pager->setMaxPerPage($maxPerPage)->setCurrentPage($currentPage);

            return $pager->getCurrentPageResults();
        }

        return $qb->getQuery()->getResult();
    }

    public function findGroupEventStats(array $data = array())
    {
        $filters = array_merge(
            array('eventName' => '', 'published' => '', 'sites' => array(), 'from' => '', 'thru' => '', 'filter' => ''),
            $data
        );

        $qb = $this->getFindEventsQB($filters['eventName'], $filters['published'], $filters['sites'], $filters['from'], $filters['thru'], $filters['filter']);

        if (isset($filters['page'])) {
            $adapter = new DoctrineORMAdapter($qb);
            $pager = new Pagerfanta($adapter);
            $pager->setMaxPerPage(10)->setCurrentPage($filters['page']);

            return $pager;
        }

        return $qb->getQuery()->execute();
    }

    public function getFindEventsQB($eventName, $status, $sites, $from="", $thru="", $filter="")
    {
        $qb = $this->createQueryBuilder('gE')
            ->leftJoin('gE.group', 'g')
            ->leftJoin('gE.sites', 's');

        if (count($sites) > 0) {
            $qb->andWhere('s IN (:siteList)');
            $qb->setParameter('siteList', $sites);

        }

        if ($eventName) {
            $qb->andWhere('gE.name like :eventName');
            $qb->setParameter('eventName', '%'.$eventName.'%');
        }

        if ($status != "") {
            $qb->andWhere('gE.published = :status');
            $qb->setParameter('status', $status);
        }

        if ($from != "") {
            $from->setTime(0, 0, 0);
            $qb->andWhere('gE.startsAt >= :from');
            $qb->setParameter('from', $from);
        }

        if ($thru != "") {
            $thru->setTime(23, 59, 59);
            $qb->andWhere('gE.startsAt <= :thru');
            $qb->setParameter('thru', $thru);
        }

        if($filter != "") {
            if($filter == "upcoming") {
                $qb->andWhere('gE.startsAt >= :now');
                $qb->setParameter('now', new DateTime('now'));
            }

            if($filter == "past") {
                $qb->andWhere('gE.startsAt <= :now');
                $qb->setParameter('now', new DateTime('now'));
            }

            if($filter == "public") {
                $qb->andWhere('gE.private = 0');
            }

            if($filter == "private") {
                $qb->andWhere('gE.private = 1');
            }

            if($filter == "inactive") {
                $qb->orWhere('gE.active <> 1');
                $qb->orWhere('gE.published <> 1');
                $qb->orWhere('gE.deleted = 1');
            }
        }

        $qb->distinct('gE.id');

        return $qb;
    }

    protected function addActiveClauses($qb, $alias='e')
    {
        return $qb->andWhere($alias.'.deleted = 0')
            ->andWhere($alias.'.approved = 1');
    }

    public function findAllForGroup($group)
    {
        return $this->getBaseGroupQueryBuilder($group)
            ->getQuery()
            ->getResult();
    }
}
