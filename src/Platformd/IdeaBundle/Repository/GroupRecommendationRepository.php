<?php

namespace Platformd\IdeaBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Platformd\IdeaBundle\Entity\GroupRecommendation;
use Platformd\UserBundle\Entity\User;

class GroupRecommendationRepository extends EntityRepository
{
    /**
     * @param User $user
     *
     * @return GroupRecommendation[]
     */
    public function getByUser(User $user)
    {
        $qb = $this->createQueryBuilder('gr');

        $qb
            ->innerJoin('gr.user', 'u')
            ->innerJoin('gr.referredBy', 'rb')
            ->where('u.id = :userId')
            ->andWhere('gr.dismissed = :dismiss')
            ->setParameters([
                'userId' => $user->getId(),
                'dismiss' => false,
            ])
        ;

        return $qb->getQuery()->getResult();
    }
}
