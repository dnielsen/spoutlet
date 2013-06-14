<?php

namespace Platformd\UserBundle\Entity;

use Doctrine\ORM\EntityRepository;

use Pagerfanta\Pagerfanta,
    Pagerfanta\Adapter\DoctrineORMAdapter
;

class AvatarRepository extends EntityRepository
{
    public function getUnapprovedCountForUser($user)
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.user = :user')
            ->andWhere('a.approved = 0')
            ->andWhere('a.deleted = 0')
            ->andWhere('a.resized = 1')
            ->andWhere('a.cropped = 1')
            ->andWhere('a.reviewed = 0')
            ->setParameters(array(
                'user' => $user,
            ))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getProcessingCountForUser($user)
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.user = :user')
            ->andWhere('a.deleted = 0')
            ->andWhere('a.cropped = 1')
            ->andWhere('(a.approved = 1 AND a.reviewed = 1 AND a.processed = 0) OR a.resized = 0')
            ->setParameters(array(
                'user' => $user,
            ))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getUnapprovedAvatars($maxPerPage = 64, $currentPage = 1, &$pager = null, $locale = null)
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.cropped = 1')
            ->andWhere('a.approved = 0')
            ->andWhere('a.reviewed = 0')
            ->andWhere('a.resized = 1')
            ->orderBy('a.createdAt')
        ;

        if ($locale) {
            $qb->leftJoin('a.user', 'u')
                ->andWhere('u.locale = :locale OR u.locale IS NULL')
                ->setParameter('locale', $locale)
            ;
        }

        if ($maxPerPage) {
            $adapter = new DoctrineORMAdapter($qb);
            $pager = new Pagerfanta($adapter);
            $pager->setMaxPerPage($maxPerPage)->setCurrentPage($currentPage);

            return $pager->getCurrentPageResults();
        }

        return $qb->getQuery()->getResult();
    }

    public function findIdsIn(array $ids = array())
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.id IN (:ids)')
            ->setParameters(array(
                'ids' => $ids,
            ))
            ->getQuery()
            ->getResult();
    }
}
