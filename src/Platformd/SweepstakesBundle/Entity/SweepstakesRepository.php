<?php

namespace Platformd\SweepstakesBundle\Entity;

use Platformd\SweepstakesBundle\Entity\Sweepstakes;
use Platformd\UserBundle\Entity\User;

use Doctrine\ORM\EntityRepository;

use DateTime;

/**
 * Repository class for Sweepstakes
 */
class SweepstakesRepository extends EntityRepository
{
    public function createNewEntry(Sweepstakes $sweepstakes, User $user, $ipAddress)
    {
        $entry = new Entry();

        $entry->setSweepstakes($sweepstakes);
        $entry->setUser($user);
        $entry->setIpAddress($ipAddress);

        return $entry;
    }

    public function findAllForSite($site)
    {
        $qb = $this->getSiteQueryBuilder($site)
            ->orderBy('ss.created', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function findAllOrderedByNewest()
    {
        return $this->createQueryBuilder('ss')
            ->orderBy('ss.created', 'DESC')
            ->getQuery()
            ->execute()
        ;
    }

    public function findOneBySlugForSite($slug, $site)
    {
        $qb = $this->getSiteQueryBuilder($site)
            ->andWhere('ss.slug = :slug')
            ->setParameter('slug', $slug)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getCurrentSweepstakes($site, $limit = null)
    {
        $qb = $this->getSiteQueryBuilder($site)
            ->andWhere('ss.published = true')
            ->andWhere('ss.hidden = false')
            ->andWhere('ss.endsAt > :now')
            ->setParameter('now', new DateTime())
        ;

        return $qb->getQuery()->execute();
    }

    public function getPastSweepstakes($site, $limit = null)
    {
        $qb = $this->getSiteQueryBuilder($site)
            ->andWhere('ss.published = true')
            ->andWhere('ss.hidden = false')
            ->andWhere('ss.endsAt <= :now')
            ->setParameter('now', new DateTime())
        ;

        return $qb->getQuery()->execute();
    }

    protected function getSiteQueryBuilder($site, $alias='ss')
    {
        $qb = $this->createQueryBuilder($alias)
            ->leftJoin($alias.'.sites', 's')
            ->andWhere(is_string($site) ? 's.name = :site' : 's = :site')
            ->setParameter('site', $site)
        ;

        return $qb;
    }

    public function findPublished($site)
    {
        $qb = $this->getSiteQueryBuilder($site)
            ->orderBy('ss.startsAt', 'DESC')
            ->andWhere('ss.hidden = false')
            ->andWhere('ss.published = true')
        ;

        return $qb->getQuery()->execute();
    }
}
