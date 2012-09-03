<?php

namespace Platformd\SpoutletBundle\Entity;

use Platformd\SpoutletBundle\Entity\Group;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use DateTime;
use DateTimeZone;

/**
 * GroupsRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class GroupRepository extends EntityRepository
{

    /**
     * @param $site
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function createSiteQueryBuilder($site, $allowAllLocaleEntries = true)
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.groupLocales', 'gl')
            ->andWhere('g.deleted = false')
            ->andWhere(($allowAllLocaleEntries ? 'g.allLocales = true or ' : 'g.allLocales = false and ') . 'gl.locale = :site')
            ->setParameter('site', $site);
    }

    private function addPublicOnlyQueryBuilder(QueryBuilder $qb)
    {
        return $qb->andWhere('g.isPublic = true');
    }

    public function findAllGroupsRelevantForSite($site) {

        return $this->getEntityManager()->createQuery('
            SELECT g FROM SpoutletBundle:Group g
            LEFT JOIN g.sites s
            WHERE g.deleted = false
            AND (g.allLocales = true OR s = :site)')
            ->setParameter('site', $site)
            ->execute();
    }

    public function findAllPublicAndPrivateGroupsForSite($site, $allowAllLocaleEntries = true)
    {
        $qb = $this->createSiteQueryBuilder($site, $allowAllLocaleEntries);

        return $qb->getQuery()->execute();
    }

    public function findGroupsByName($groupName)
    {
        $qb = $this->createQueryBuilder('g')
            ->where('g.name like :groupName')
            ->setParameter('groupName', '%'.$groupName.'%');

        return $qb->getQuery()->execute();
    }

    public function findGroupsByNameAndSite($groupName, $site)
    {
        $qb = $this->createQueryBuilder('g')
            ->leftJoin('g.groupLocales', 'gl')
            ->where('g.name like :groupName')
            ->andWhere('gl.locale = :site')
            ->setParameter('groupName', '%'.$groupName.'%')
            ->setParameter('site', $site);

        return $qb->getQuery()->execute();
    }

    public function getUserCountForGroupsBySite($site)
    {

    }
}
