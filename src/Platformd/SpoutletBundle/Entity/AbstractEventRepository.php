<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\Query;
use Doctrine\ORM\EntityRepository;
use DateTime;
use Doctrine\ORM\QueryBuilder;
use Platformd\GiveawayBundle\Entity\Giveaway;
use Platformd\SweepstakesBundle\Entity\Sweepstakes;

/**
 * Repository for the base, abstract "events"
 */
class AbstractEventRepository extends EntityRepository
{
    /**
     * Return current AND upcoming events (that are published of course)
     *
     * @param integer $limit
     * @return array
     */
    public function getCurrentEvents($site, $limit = null)
    {
        $qb = $this->getBaseQueryBuilder($site);
        $query = $this->addActiveQuery($qb)
            ->orderBy('e.starts_at', 'DESC')
            ->getQuery()
        ;

        $items = $this->addQueryLimit($query, $limit)->getResult();
        $items = $this->removeDisabledGiveaways($items);

        return $items;
    }

    /**
     * The same as getCurrentEvents(), but ordered by created
     *
     * @param $site
     * @param null $limit
     * @return array|mixed
     */
    public function getCurrentEventsOrderedByCreated($site, $limit = null)
    {
        $qb = $this->getBaseQueryBuilder($site);
        $query = $this->addActiveQuery($qb)
            ->orderBy('e.created', 'DESC')
            ->getQuery()
        ;

        $items = $this->addQueryLimit($query, $limit)->getResult();
        $items = $this->removeDisabledGiveaways($items);

        return $items;
    }

    /**
     * A funky little function that only return Events and Sweepstakes
     *
     * @param string $site
     * @param integer $limit
     * @return array
     */
    public function getCurrentEventsAndSweepstakes($site, $limit = null)
    {
        $abstractEvents = $this->getCurrentEvents($site, $limit);

        foreach ($abstractEvents as $key => $value) {
            // unset if it's not an event or sweepstakes
            if (!($value instanceof Event) && !($value instanceof Sweepstakes)) {
                unset($abstractEvents[$key]);
            }
        }

        return $abstractEvents;
    }

    /**
     * A funky little function that only return Events and Sweepstakes
     *
     * @param string $site
     * @param integer $limit
     * @return array
     */
    public function getCurrentSweepstakes($site, $limit = null)
    {
        $abstractEvents = $this->getCurrentEvents($site, $limit);

        foreach ($abstractEvents as $key => $value) {
            // unset if it's not an event or sweepstakes
            if (!($value instanceof Sweepstakes)) {
                unset($abstractEvents[$key]);
            }
        }

        return $abstractEvents;
    }


    /**
     * A funky little function that only return Events and Sweepstakes
     *
     * @param string $site
     * @param integer $limit
     * @return array
     */
    public function getCurrentEventsOnly($site, $limit = null)
    {
        $abstractEvents = $this->getCurrentEvents($site, $limit);

        foreach ($abstractEvents as $key => $value) {
            // unset if it's not an event or sweepstakes
            if (!($value instanceof Event)) {
                unset($abstractEvents[$key]);
            }
        }

        return $abstractEvents;
    }

    /**
     * Return past events
     *
     * @param integer $limit
     * @return array
     */
    public function getPastEvents($site, $limit = null)
    {
        $query = $this->getBaseQueryBuilder($site)
            ->andWhere('e.ends_at < :cut_off')
            ->setParameter('cut_off', new \DateTime())
            ->orderBy('e.ends_at', 'DESC')
            ->getQuery();

        return $this->addQueryLimit($query, $limit)->getResult();
    }

    /**
     * A funky little function that only return Events and Sweepstakes
     *
     * @param string $site
     * @param integer $limit
     * @return array
     */
    public function getPastEventsAndSweepstakes($site, $limit = null)
    {
        $abstractEvents = $this->getPastEvents($site, $limit);

        foreach ($abstractEvents as $key => $value) {
            // unset if it's not an event or sweepstakes
            if (!($value instanceof Event) && !($value instanceof Sweepstakes)) {
                unset($abstractEvents[$key]);
            }
        }

        return $abstractEvents;
    }

    /**
     * Retrieve published events
     *
     * @return array
     */
    public function findPublished($site)
    {
        $items = $this->createQueryBuilder('e')
            ->andWhere(':site IN e.sites')
            ->andWhere('e.published = :published')
            ->setParameters(array(
                'site'    => $site,
                'published' => true,
            ))
            ->orderBy('e.starts_at', 'DESC')
            ->getQuery()
            ->execute()
        ;

        $items = $this->removeDisabledGiveaways($items);

        return $items;
    }

    public function findOnePublishedBySlug($slug, $site)
    {
        $result = $this->getBaseQueryBuilder($site)
            ->andWhere("e.slug = :slug")
            ->andWhere("e.published = true")
            ->setParameter('slug', $slug)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        return $result && count($result) > 0 ? $result[0] : null;
    }

    public function findOneBySlug($slug, $site)
    {
        $result = $this->getBaseQueryBuilder($site)
            ->andWhere("e.slug = :slug")
            ->setParameter('slug', $slug)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        return $result && count($result) > 0 ? $result[0] : null;
    }

    public function findAllWithoutLocaleOrderedByNewest()
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.created', 'DESC')
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @param Game $game
     * @param $siteKey
     * @return \Platformd\SpoutletBundle\Entity\AbstractEvent[]
     */
    public function findActivesForGame(Game $game, $site)
    {
        $qb = $this->getBaseQueryBuilder($site);
        $query = $this->addActiveQuery($qb)
            ->orderBy('e.created', 'DESC')
            ->andWhere('e.game = :game')
            ->setParameter('game', $game)
            ->getQuery()
        ;

        $items = $query->getResult();
        $items = $this->removeDisabledGiveaways($items);

        return $items;
    }

    /**
     * Adds the "is active" OR upcoming part of the query by date
     *
     * This allows the starts_at or ends_at to be null, and for that to be "active"
     *
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function addActiveQuery(QueryBuilder $qb)
    {
        $qb
            ->andWhere('e.ends_at > :cut_off OR e.ends_at IS NULL')
            ->setParameter('cut_off', new \DateTime())
        ;

        return $qb;
    }

    /**
     * Return a query builder instance that should be used for frontend request
     * basically, it only adds a criteria to retrieve only published events
     *
     * @param String $alias
     * @return Doctrine\ORM\QueryBuilder
     */
    private function getBaseQueryBuilder($site, $alias = 'e')
    {
        $qb = $this->createQueryBuilder($alias)
            ->leftJoin($alias.'.sites', 's')
            ->andWhere($alias.'.published = 1');

        if (is_string($site)) {
            $qb->andWhere('s.name = :site')
                ->setParameter('site', $site);

            return $qb;
        }

        $qb->andWhere('s = :site')
            ->setParameter('site', $site);

        return $qb;
    }

    private function addQueryLimit(Query $query, $limit)
    {
        if  (null === $limit) {
            return $query;
        }

        return $query->setMaxResults($limit);
    }

    /**
     * A hack - see #18
     *
     * @param $abstractEvents
     * @return mixed
     */
    private function removeDisabledGiveaways($abstractEvents)
    {
        foreach ($abstractEvents as $key => $item) {
            // todo - remove this hack - see #18
            if ($item instanceof Giveaway && $item->isDisabled()) {
                unset($abstractEvents[$key]);
            }
        }

        return $abstractEvents;
    }
}
