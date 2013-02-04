<?php

namespace Platformd\EventBundle\Repository;

use Doctrine\ORM\EntityManager,
    Doctrine\ORM\EntityRepository
;

use Platformd\EventBundle\Entity\Event;
use Platformd\UserBundle\Entity\User;

abstract class EventRepository extends EntityRepository
{
    /**
     * @param \Doctrine\ORM\EntityManager $entityManager
     * @param \Doctrine\ORM\Mapping\ClassMetadata $class
     */
    /*public function __construct(EntityManager $entityManager, $class)
    {
        $metadata = $entityManager->getClassMetadata($class);
        parent::__construct($entityManager, $metadata);
    }*/

    /**
     * Persists Event in the DB
     *
     * @param \Platformd\EventBundle\Entity\Event $event
     */
    public function saveEvent(Event $event)
    {
        $this->_em->persist($event);
        $this->_em->flush();
    }

    /**
     * Returns list of events that the user is registered for or owns
     *
     * @param \Platformd\SpoutletBundle\Entity\User $user
     * @param boolean $whereIsOrganizer
     */
    public function getEventListForUser(User $user, $whereIsOrganizer = false)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e', 'count(a.id) attendeeCount')
            ->leftJoin('e.attendees', 'a')
            ->andWhere('e.endsAt >= :now')
            ->groupBy('e.id')
            ->setParameters(array(
                'user' => $user,
                'now' => new \DateTime('now'),
            ));

        if ($whereIsOrganizer) {
            $qb->andWhere('e.user = :user');
        } else {
            $qb->andWhere('e.id IN (SELECT ge2.id FROM EventBundle:GroupEvent ge2 LEFT JOIN ge2.attendees a2 WHERE a2=:user)')
                ->andWhere('e.user <> :user');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns list of past events that the user is registered for
     *
     * @param \Platformd\SpoutletBundle\Entity\User $user
     */
    public function getPastEventListForUser(User $user)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e', 'count(a.id) attendeeCount')
            ->leftJoin('e.attendees', 'a')
            ->andWhere('e.id IN (SELECT ge2.id FROM EventBundle:GroupEvent ge2 LEFT JOIN ge2.attendees a2 WHERE a2=:user)')
            ->andWhere('e.endsAt < :now')
            ->groupBy('e.id')
            ->setParameters(array(
                'user' => $user,
                'now' => new \DateTime('now'),
            ));

        return $qb->getQuery()->getResult();
    }
}
