<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * ContestRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ContestRepository extends EntityRepository
{
    public function findAllForSiteAlphabetically($site) {

        return $this->createSiteQueryBuilder($site)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->execute();
    }

    public function findAllForMetrics() {

        return $this->createQueryBuilder('c')
            ->leftJoin('c.entries', 'e')
            /*->leftJoin('e.votes', 'v')
            ->select('c', 'COUNT(e)', 'COUNT(v)')*/
            ->select('c', 'COUNT(e)')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->execute();
    }

    private function createSiteQueryBuilder($site)
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.sites', 's')
            ->where('s.defaultLocale = :site')
            ->setParameter('site', $site)
        ;

        return $qb;
    }

    public function canUserVoteBasedOnSite($user, $contest)
    {
        $result = $this->createQueryBuilder('c')
            ->leftJoin('c.sites', 's')
            ->andWhere('s.defaultLocale = :locale')
            ->setParameter('locale', $user->getLocale())
            ->getQuery()
            ->execute();

        return $result ? true : false;
    }
}
