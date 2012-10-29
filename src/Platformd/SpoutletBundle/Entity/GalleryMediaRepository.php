<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * GalleryRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class GalleryMediaRepository extends EntityRepository
{
    public function findAllUnpublishedByUser($user)
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.author = :user')
            ->andWhere('gm.published = :published')
            ->setParameter('user', $user)
            ->setParameter('published', false)
            ->getQuery()
            ->execute();
    }
}
