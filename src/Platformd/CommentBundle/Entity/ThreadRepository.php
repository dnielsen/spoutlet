<?php

namespace Platformd\CommentBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;


class ThreadRepository extends EntityRepository
{

    public function getTotalCommentsByThreadId($id)
    {
        try {
        return $query = $this->createQueryBuilder('t')
            ->select('t.numComments')
            ->where('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getSingleScalarResult();
        }
        catch (NoResultException $e) {
            return 0;
        }
    }
}
