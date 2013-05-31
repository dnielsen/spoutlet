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

    public function findAllQB()
    {
        return $this->createQueryBuilder('avatar')
            ->addSelect('m')
            ->leftJoin('avatar.media', 'm')
        ;
    }

    public function setApprovals(array $approvedIds, array $disapprovedIds)
    {
        $self = $this;
        $this->_em->transactional(function($em) use($self, $approvedIds, $disapprovedIds) {
            $self->batchSetApprove($approvedIds, true);
            $self->batchSetApprove($disapprovedIds, 0); // 0 = false
        });
    }

    public function batchSetApprove(array $ids, $isApproved)
    {
        if (empty($ids)) {
            return;
        }
        return $this->createQueryBuilder('avatar')
            ->update('Platformd\UserBundle\Entity\UserAvatar', 'avatar')
            ->set('avatar.isApproved', $isApproved)
            ->where('avatar.id IN( :ids )')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute()
        ;
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


