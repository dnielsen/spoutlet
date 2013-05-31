<?php

namespace Platformd\UserBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Platformd\UserBundle\Entity\User;

class UserAvatarRepository extends EntityRepository
{
    public function getByUserAndId(User $user, $id)
    {
        return $this->findOneBy(array(
            'id' => $id,
            'user' => $user->getId(),
        ));
    }

    public function unSelectAll(User $user)
    {
        $this->createQueryBuilder('avatar')
            ->update('Platformd\UserBundle\Entity\UserAvatar', 'avatar')
            ->set('avatar.isSelected', 0)
            ->where('avatar.user = :userId')
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->execute()
        ;
    }

    public function toggle($avatar)
    {
        $self = $this;
        $this->_em->transactional(function($em) use($avatar, $self) {
            $self->unSelectAll($avatar->getUser());
            $avatar->toggleSelected();
        });
    }
}

