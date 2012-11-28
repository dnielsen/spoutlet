<?php

namespace Platformd\CommentBundle\Entity;

use Doctrine\ORM\EntityRepository;


class ThreadRepository extends EntityRepository
{

    public function getTotalCommentsByThreadId($id)
    {
        return $query = $this->createQueryBuilder('t')
            ->select('t.numComments')
            ->where('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }
}
