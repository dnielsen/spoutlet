<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;

class ThreadRepository extends EntityRepository
{
    public function getTotalCommentsByThreadId($id)
    {
        try {
        return $query = $this->createQueryBuilder('t')
            ->select('t.commentCount')
            ->where('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getSingleScalarResult();
        }
        catch (NoResultException $e) {
            return 0;
        }
    }

    public function findCommentCountsForThreadIds($threadIds)
    {
        $result = $this->createQueryBuilder('t')
            ->select('t.id, t.commentCount')
            ->andWhere('t.id IN (:ids)')
            ->setParameter('ids', $threadIds)
            ->getQuery()
            ->getResult();

        $counts = array();

        foreach ($result as $data) {
            $counts[$data['id']] = $data['commentCount'];
        }

        return $counts;
    }
}
