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

    public function findAllForSiteByDate($site, $status=array('published'))
    {
        $qb = $this->createQueryBuilder('c');

        return $qb->leftJoin('c.sites', 's')
            ->where('c.votingEnd > :today')
            ->andWhere('s.defaultLocale = :site')
            ->andWhere($qb->expr()->in('c.status', $status))
            ->setParameter('site', $site)
            ->setParameter('today', new \DateTime('now'))
            ->orderBy('c.votingEnd', 'DESC')
            ->getQuery()
            ->execute();
    }

    public function findAllAlphabetically()
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->execute();
    }

    private function createSiteQueryBuilder($site)
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.sites', 's')
            ->where('s.defaultLocale = :site OR s = :site')
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

    public function findAllByCategoryAndSiteWithVotingPeriod($category, $site, $status=array('published'))
    {
        $qb = $this->createSiteQueryBuilder($site);

        $qb->andWhere($qb->expr()->in('c.status', $status))
            ->andWhere('c.votingEnd > :today')
            ->andWhere('c.category = :category')
            ->orderBy('c.votingEnd', 'DESC')
            ->setParameter('category', $category)
            ->setParameter('today', new \DateTime('now'));

        return $qb->getQuery()->execute();
    }

    public function findAllByCategoryAndSite($category, $site, $status=array('published'))
    {
        $qb = $this->createSiteQueryBuilder($site);

        $qb->andWhere($qb->expr()->in('c.status', $status))
            ->andWhere('c.category = :category')
            ->orderBy('c.votingEnd', 'DESC')
            ->setParameter('category', $category);

        return $qb->getQuery()->execute();
    }

    public function findAllBySite($site, $status=array('published'))
    {
        $qb = $this->createSiteQueryBuilder($site);

        $qb->andWhere($qb->expr()->in('c.status', $status))
            ->orderBy('c.votingEnd', 'DESC')
        ;

        return $qb->getQuery()->execute();
    }

    public function findAllByCategory($category)
    {
        return $this->createQueryBuilder('c')
            ->where('c.category = :category')
            ->setParameter('category', $category)
            ->getQuery()
            ->execute();
    }

    public function findAllByCategoryOrderdByVoteEnding($category, $sortDir='DESC')
    {
        return $this->createQueryBuilder('c')
            ->where('c.category = :category')
            ->orderBy('c.votingEnd', $sortDir)
            ->setParameter('category', $category)
            ->getQuery()
            ->execute();
    }

    public function findAllExpiredBySite($site)
    {
        return $this->createSiteQueryBuilder($site)
            ->andWhere('c.votingEnd < :today')
            ->andWhere('c.status = :status')
            ->setParameter('today', new \DateTime('now'))
            ->setParameter('status', 'published')
            ->getQuery()
            ->execute();
    }

    public function findContestByGroup($group)
    {
        $qb = $this->createQueryBuilder('c');
        return $qb->leftJoin('c.entries', 'e')
            ->leftJoin('e.groups', 'eg')
            ->where($qb->expr()->in('eg.id', array($group->getId())))
            ->andWhere('c.status = :status')
            ->setParameter('status', 'published')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findContestsByGroups($groups)
    {
        $ids = array(0);

        foreach ($groups as $group) {
            array_push($ids, $group[0]->getId());
        }

        $qb = $this->createQueryBuilder('c');
        $results = $qb->select('eg.id')
            ->leftJoin('c.entries', 'e')
            ->leftJoin('e.groups', 'eg')
            ->where($qb->expr()->in('eg.id', $ids))
            ->andWhere('c.status = :status')
            ->setParameter('status', 'published')
            ->getQuery()
            ->execute();

        $found = array();

        foreach ($results as $result) {
            array_push($found, $result["id"]);
        }

        return $found;
    }
}
