<?php

namespace Platformd\EventBundle\Repository;

use Platformd\EventBundle\Repository\EventRepository,
    Platformd\SpoutletBundle\Entity\Site,
    Platformd\GameBundle\Entity\Game
;

use Pagerfanta\Pagerfanta,
    Pagerfanta\Adapter\DoctrineORMAdapter
;

use DateTime;

class GlobalEventRepository extends EventRepository
{
    public function findOneBySlugForSite($slug, Site $site)
    {
        $qb = $this->createQueryBuilder('gE')
            ->select('gE', 's')
            ->leftJoin('gE.sites', 's')
            ->where('gE.slug = :slug')
            ->andWhere('s = :site')
            ->setParameter('slug', $slug)
            ->setParameter('site', $site)
        ;

        return $qb->getQuery()->getOneOrNullResult();
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
        $qb = $this->createQueryBuilder('gE')
            ->select('gE', 's')
            ->leftJoin('gE.sites', 's')
            ->where('gE.endsAt >= :now')
            ->andWhere('gE.published = :published')
            ->andWhere('s = :site')
            ->orderBy('gE.createdAt', 'DESC')
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
        $qb = $this->createQueryBuilder('gE')
            ->select('gE', 's')
            ->leftJoin('gE.sites', 's')
            ->where('gE.endsAt < :now')
            ->andWhere('gE.published = :published')
            ->andWhere('s = :site')
            ->orderBy('gE.createdAt', 'DESC')
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
     * @param \Platformd\SpoutletBundle\Entity\Site $site
     * @param \Platformd\GameBundle\Entity\Game $game
     * @param bool $published
     */
    public function findEventsForGamePage(Site $site, Game $game, $published = true)
    {
        $qb = $this->createQueryBuilder('gE')
            ->select('gE', 's')
            ->leftJoin('gE.sites', 's')
            ->leftJoin('gE.game', 'g')
            ->where('gE.endsAt > :now')
            ->andWhere('gE.published = :published')
            ->andWhere('s = :site')
            ->andWhere('g = :game')
            ->orderBy('gE.createdAt', 'DESC')
            ->setParameter('now', new DateTime('now'))
            ->setParameter('published', $published)
            ->setParameter('site', $site)
            ->setParameter('game', $game)
        ;

        return $qb->getQuery()->getResult();
    }

    public function findGlobalEventStats(array $data = array())
    {
        $filters = array_merge(
            array('eventName' => '', 'published' => '', 'sites' => array(), 'startDate' => '', 'endDate' => ''),
            $data
        );

        $qb = $this->getFindEventsQB($filters['eventName'], $filters['published'], $filters['sites'], $filters['from'], $filters['thru']);

        if (isset($filters['page'])) {
            $adapter = new DoctrineORMAdapter($qb);
            $pager = new Pagerfanta($adapter);
            $pager->setMaxPerPage(10)->setCurrentPage($filters['page']);

            return $pager;
        }

        return $qb->getQuery()->execute();
    }

    public function getFindEventsQB($eventName, $status, $sites, $from="", $thru="")
    {
        $qb = $this->createQueryBuilder('gE')
            ->leftJoin('gE.sites', 's');

        if (count($sites) > 0) {

            $qb->andWhere('s.defaultLocale IN (:siteList)');
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
            $qb->andWhere('gE.endsAt <= :thru');
            $qb->setParameter('thru', $thru);
        }

        $qb->distinct('gE.id');

        return $qb;
    }

    public function findAllForSite($site)
    {
        $qb = $this->createQueryBuilder('gE')
            ->select('gE', 's')
            ->leftJoin('gE.sites', 's')
            ->andWhere('s = :site')
            ->setParameter('site', $site)
            ->orderBy('gE.createdAt', 'DESC')
        ;

        return $qb->getQuery()->getResult();
    }
}
