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
            ->where('gE.startsAt >= :now')
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
            ->where('gE.startsAt < :now')
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
}
