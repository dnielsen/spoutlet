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

    public function findAllForSite($site, $type=Sweepstakes::SWEEPSTAKES_TYPE_SWEEPSTAKES)
    {
        $qb = $this->getSiteQueryBuilder($site)
            ->orderBy('ss.created', 'DESC');

        $this->addTypeQuery($qb, $type);

        return $qb->getQuery()->getResult();
    }

    public function findOneBySlugForSite($slug, $site, $type=Sweepstakes::SWEEPSTAKES_TYPE_SWEEPSTAKES)
    {
        $qb = $this->getSiteQueryBuilder($site)
            ->andWhere('ss.slug = :slug')
            ->setParameter('slug', $slug)
        ;

        $this->addTypeQuery($qb, $type);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getCurrentSweepstakes($site, $limit = null, $type=Sweepstakes::SWEEPSTAKES_TYPE_SWEEPSTAKES)
    {
        $qb = $this->getSiteQueryBuilder($site)
            ->andWhere('ss.published = true')
            ->andWhere('ss.hidden = false')
            ->andWhere('ss.endsAt > :now')
            ->setParameter('now', new DateTime())
        ;

        $this->addTypeQuery($qb, $type);

        return $qb->getQuery()->execute();
    }

    public function getPastSweepstakes($site, $limit = null, $type=Sweepstakes::SWEEPSTAKES_TYPE_SWEEPSTAKES)
    {
        $qb = $this->getSiteQueryBuilder($site)
            ->andWhere('ss.published = true')
            ->andWhere('ss.hidden = false')
            ->andWhere('ss.endsAt <= :now')
            ->setParameter('now', new DateTime())
        ;

        $this->addTypeQuery($qb, $type);

        return $qb->getQuery()->execute();
    }

    protected function getSiteQueryBuilder($site, $alias='ss')
    {
        $qb = $this->createQueryBuilder($alias);

        if ($site) {
            $qb->leftJoin($alias.'.sites', 's')
                ->andWhere(is_string($site) ? 's.name = :site' : 's = :site')
                ->setParameter('site', $site)
            ;
        }

        return $qb;
    }

    public function findPublished($site, $type=Sweepstakes::SWEEPSTAKES_TYPE_SWEEPSTAKES)
    {
        $qb = $this->getSiteQueryBuilder($site)
            ->orderBy('ss.startsAt', 'DESC')
            ->andWhere('ss.hidden = false')
            ->andWhere('ss.published = true')
        ;

        $this->addTypeQuery($qb, $type);

        return $qb->getQuery()->execute();
    }

    private function addTypeQuery($qb, $type, $alias='ss')
    {
        $qb->andWhere($alias.'.eventType = :type')
            ->setParameter('type', $type);
    }
}
