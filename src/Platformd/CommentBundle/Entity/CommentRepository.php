<?php

namespace Platformd\CommentBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;


class CommentRepository extends EntityRepository
{
    /**
     * Used if you want to return all the latest comments from a specific type (e.g. deals)
     *
     * The prefix is not used across all threads yet, but it works if the
     * type you want uses the prefix (e.g. deal-)
     *
     * @param $prefix
     * @param int $count
     * @return Comment[]
     */
    public function findMostRecentCommentsByThreadPrefix($prefix, $count = 5)
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.thread', 't')
            ->andWhere('t.id LIKE :prefix')
            ->setParameter('prefix', $prefix.'%')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($count)
            ->getQuery()
            ->execute()
        ;
    }

    public function getCommentCountByThread($thread, $fromDate=null, $thruDate=null)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('COUNT(c.id)');
        $qb->where('c.thread = :thread');
        $qb->setParameter('thread', $thread);

        if($fromDate != null and $thruDate != null)
        {
            $qb->andWhere('c.createdAt >= :fromDate')
               ->andWhere('c.createdAt <= :thruDate')
               ->setParameter('fromDate', $fromDate)
               ->setParameter('thruDate', $thruDate);
        }
        try {
        $total = $qb->getQuery()->getSingleScalarResult();
        }
        catch (NoResultException $e) {
            return 0;
        }

        return $total;
    }
}
