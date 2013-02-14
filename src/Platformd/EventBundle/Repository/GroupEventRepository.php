<?php

namespace Platformd\EventBundle\Repository;

use Platformd\SpoutletBundle\Entity\Group;
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
            ->orderBy('e.startsAt')
            ->setParameter('now', new \DateTime('now'));

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
    public function findUpcomingEventsForSite(Site $site, $maxPerPage = 20, $currentPage = 1, &$pager, $published = true)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e', 's')
            ->leftJoin('e.sites', 's')
            ->leftJoin('e.group', 'g')
            ->where('e.endsAt >= :now')
            ->andWhere('e.published = :published')
            ->andWhere('s = :site')
            ->orderBy('e.createdAt', 'DESC')
            ->setParameter('now', new DateTime())
            ->setParameter('published', $published)
            ->setParameter('site', $site)
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
     * Returns all past events, ability to paginate
     *
     * @param \Platformd\SpoutletBundle\Entity\Site $site
     * @param int $maxPerPage
     * @param int $currentPage
     * @param $pager
     * @param bool $published
     * @return array
     */
    public function findPastEventsForSite(Site $site, $maxPerPage = 20, $currentPage = 1, &$pager, $published = true)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e', 's')
            ->leftJoin('e.sites', 's')
            ->leftJoin('e.group', 'g')
            ->where('e.endsAt < :now')
            ->andWhere('e.published = :published')
            ->andWhere('s = :site')
            ->orderBy('e.createdAt', 'DESC')
            ->setParameter('now', new DateTime())
            ->setParameter('published', $published)
            ->setParameter('site', $site)
        ;

        if ($maxPerPage) {
            $adapter = new DoctrineORMAdapter($qb);
            $pager = new Pagerfanta($adapter);
            $pager->setMaxPerPage($maxPerPage)->setCurrentPage($currentPage);

            return $pager->getCurrentPageResults();
        }

        return $qb->getQuery()->getResult();
    }

    protected function addActiveClauses($qb, $alias='e')
    {
        return $qb->andWhere($alias.'.deleted = 0')
            ->andWhere($alias.'.approved = 1');
    }
}
