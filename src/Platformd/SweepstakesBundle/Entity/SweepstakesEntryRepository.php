<?php

namespace Platformd\SweepstakesBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Platformd\SweepstakesBundle\Entity\Sweepstakes;
use Platformd\UserBundle\Entity\User;

/**
 * SweepstakesEntryRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SweepstakesEntryRepository extends EntityRepository
{
    public function findOneBySweepstakesAndUser(Sweepstakes $sweepstakes, User $user)
    {
        $res = $this->createQueryBuilder('e')
            ->andWhere('e.sweepstakes = :sweepstakes')
            ->andWhere('e.user = :user')
            ->setParameters(array(
                'user' => $user,
                'sweepstakes' => $sweepstakes,
            ))
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;

        return $res;
    }

    public function findOneByIdAndUser($id, User $user)
    {
        return $this
            ->createQueryBuilder('e')
            ->where('e.user = :user')
            ->andWhere('e.id = :id')
            ->setParameters(array(
                'id'    => $id,
                'user'  => $user,
            ))
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllOrderedByNewest(Sweepstakes $sweepstakes)
    {
        return $this->createQueryBuilder('e')
            ->addSelect('r.name regionName')
            ->leftJoin('e.sweepstakes','ss')
            ->leftJoin('e.country', 'c')
            ->leftJoin('c.regions', 'r')
            ->orderBy('e.created', 'DESC')
            ->andWhere('e.sweepstakes = :sweepstakes')
            ->setParameter('sweepstakes', $sweepstakes)
            ->getQuery()
            ->execute()
        ;
    }

    public function findAllForRegionOrderedByNewest(Sweepstakes $sweepstakes, $regionId)
    {
        return $this->createQueryBuilder('e')
            ->addSelect('r.name regionName')
            ->leftJoin('e.sweepstakes','ss')
            ->leftJoin('e.country', 'c')
            ->leftJoin('c.regions', 'r')
            ->andWhere('r.id = :regionId')
            ->andWhere('e.sweepstakes = :sweepstakes')
            ->orderBy('e.created', 'DESC')
            ->setParameter('sweepstakes', $sweepstakes)
            ->setParameter('regionId', $regionId)
            ->getQuery()
            ->execute()
        ;
    }

    public function findAllWithoutRegionOrderedByNewest(Sweepstakes $sweepstakes)
    {
        return $this->createQueryBuilder('e')
            ->addSelect('r.name regionName')
            ->leftJoin('e.sweepstakes','ss')
            ->leftJoin('e.country', 'c')
            ->leftJoin('c.regions', 'r')
            ->orderBy('e.created', 'DESC')
            ->andWhere('e.sweepstakes = :sweepstakes')
            ->andWhere('r.id IS NULL')
            ->setParameter('sweepstakes', $sweepstakes)
            ->getQuery()
            ->execute()
        ;
    }

    public function getTotalEntryCounts($site = null)
    {
        $qb  = $this->createQueryBuilder('e')
            ->select('COUNT(e.id) AS entryCount', 'ss.id AS sweepstakesId')
            ->leftJoin('e.sweepstakes','ss')
            ->addGroupBy('ss.id');

        if ($site) {
            $qb->leftJoin('ss.sites', 's')
                ->andWhere('s = :site')
                ->setParameter('site', $site);
        }

        return $qb->getQuery()
            ->getResult();
    }

    public function getRegionCounts($site = null)
    {
        $qb  = $this->createQueryBuilder('e')
            ->select('COUNT(e.id) AS entryCount', 'ss.id AS sweepstakesId', 'ss.name AS sweepstakesName', 'r.name AS regionName')
            ->leftJoin('e.sweepstakes','ss')
            ->leftJoin('e.country', 'c')
            ->leftJoin('c.regions', 'r')
            ->andWhere('r.site IS NOT NULL')
            ->addGroupBy('r.name')
            ->addGroupBy('ss.name');

        if ($site) {
            $qb->leftJoin('ss.sites', 's')
                ->andWhere('s = :site')
                ->setParameter('site', $site);
        }

        return $qb->getQuery()
            ->getResult();
    }
}
