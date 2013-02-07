<?php

namespace Platformd\EventBundle\Repository;

use Doctrine\ORM\EntityManager,
    Doctrine\ORM\EntityRepository
;

use Platformd\EventBundle\Entity\Event;
use Platformd\UserBundle\Entity\User;

class EventRepository extends EntityRepository
{
    /**
     * @param \Doctrine\ORM\EntityManager $entityManager
     * @param \Doctrine\ORM\Mapping\ClassMetadata $class
     */
    public function __construct(EntityManager $entityManager, $class)
    {
        $metadata = $entityManager->getClassMetadata($class);
        parent::__construct($entityManager, $metadata);
    }

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
     * Returns all events that a user has ever created
     *
     * @param \Platformd\UserBundle\Entity\User $user
     * @return array
     */
    public function getAllOwnedEventsForUser(User $user)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e')
            ->where('e.user = :user')
            ->setParameter('user', $user)
        ;

        return $qb->getQuery()->getResult();
    }

    public function getAttendeeCount($event)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(a.id) attendeeCount')
            ->leftJoin('e.attendees', 'a');

        return $qb->getQuery()
            ->getSingleScalarResult();
    }
}
