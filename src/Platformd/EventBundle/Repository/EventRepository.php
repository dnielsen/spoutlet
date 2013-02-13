<?php

namespace Platformd\EventBundle\Repository;

use Doctrine\ORM\EntityManager,
    Doctrine\ORM\EntityRepository
;

use Platformd\EventBundle\Entity\Event;
use Platformd\EventBundle\Entity\EventEmail;
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
            ->leftJoin('e.attendees', 'a')
            ->andWhere('e = :event')
            ->setParameter('event', $event);

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    public function getAttendeeList($event)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('a.id, a.username')
            ->leftJoin('e.attendees', 'a')
            ->andWhere('e = :event')
            ->setParameter('event', $event);

        return $qb->getQuery()
            ->getResult();
    }

    public function isUserAttending(Event $event, User $user)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(a.id) attendeeCount')
            ->leftJoin('e.attendees', 'a')
            ->andWhere('e = :event')
            ->andWhere('a = :user')
            ->setParameter('event', $event)
            ->setParameter('user', $user);

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Persists EventEmail in the DB
     *
     * @param \Platformd\EventBundle\Entity\EventEmail $email
     */
    public function saveEmail(EventEmail $email)
    {
        $this->_em->persist($email);
        $this->_em->flush();
    }

    /**
     * Returns list of events that the user is registered for or owns
     *
     * @param \Platformd\SpoutletBundle\Entity\User $user
     * @param boolean $whereIsOrganizer
     */
    public function getUpcomingEventListForUser(User $user, $whereIsOrganizer = false)
    {
        $subquery = $this->createQueryBuilder('e2')
            ->select('e2.id')
            ->leftJoin('e2.attendees', 'a2')
            ->andWhere('a2 = :user')
            ->getDQL();

        $qb = $this->createQueryBuilder('e')
            ->select('e', 'count(a.id) attendeeCount')
            ->leftJoin('e.attendees', 'a')
            ->andWhere('e.endsAt >= :now')
            ->groupBy('e.id')
            ->setParameters(array(
                'user' => $user,
                'now' => new \DateTime('now'),
            ));

        if (method_exists($this, 'addActiveClauses')) {
            $this->addActiveClauses($qb);
        }

        if ($whereIsOrganizer) {
            $qb->andWhere('e.user = :user');
        } else {
            $qb->andWhere($qb->expr()->in('e.id', $subquery))
                ->andWhere('e.user <> :user')
                ->andWhere('e.published = 1');
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
        $subquery = $this->createQueryBuilder('e2')
            ->select('e2.id')
            ->leftJoin('e2.attendees', 'a2')
            ->andWhere('a2 = :user')
            ->getDQL();

        $qb = $this->createQueryBuilder('e');

        $qb->select('e', 'count(a.id) attendeeCount')
            ->leftJoin('e.attendees', 'a')
            ->andWhere($qb->expr()->in('e.id', $subquery))
            ->andWhere('e.endsAt < :now')
            ->andWhere('e.published = 1')
            ->groupBy('e.id')
            ->setParameters(array(
                'user' => $user,
                'now' => new \DateTime('now'),
            ));

        if (method_exists($this, 'addActiveClauses')) {
            $this->addActiveClauses($qb);
        }

        return $qb->getQuery()->getResult();
    }
}
