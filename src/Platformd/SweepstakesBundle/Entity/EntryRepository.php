<?php

namespace Platformd\SweepstakesBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Platformd\SweepstakesBundle\Entity\Sweepstakes;
use Platformd\UserBundle\Entity\User;

/**
 * EntryRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EntryRepository extends EntityRepository
{
    public function findOneBySweepstakesAndUser(Sweepstakes $sweepstakes, User $user)
    {
        $res = $this->createQueryBuilder('e')
            ->andWhere('e.sweepstakes = :sweepstakes')
            ->andWhere('e.user = :user')
            ->setParameters(array(
                'user' => $user,
                'sweepstakes' => $sweepstakes,
            ))
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;

        return $res;
    }

    /**
     * @param $id
     * @param \Platformd\UserBundle\Entity\User $user
     * @return \Platformd\SweepstakesBundle\Entity\Entry
     */
    public function findOneByIdAndUser($id, User $user)
    {
        return $this
            ->createQueryBuilder('e')
            ->where('e.user = :user')
            ->andWhere('e.id = :id')
            ->setParameters(array(
                'id'    => $id,
                'user'  => $user,
            ))
            ->getQuery()
            ->getOneOrNullResult();
    }
}