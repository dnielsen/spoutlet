<?php

namespace Platformd\EventBundle\Repository;

use Doctrine\ORM\EntityManager,
    Doctrine\ORM\EntityRepository
;

use Platformd\EventBundle\Entity\Event;
use Platformd\EventBundle\Entity\GroupEventRepository;
use Platformd\EventBundle\Entity\EventEmail;
use Platformd\UserBundle\Entity\User;
use DateTime;

use Pagerfanta\Pagerfanta,
    Pagerfanta\Adapter\DoctrineORMAdapter
;

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

    public function getAttendeeList($event)
    {
        $result = $this->createQueryBuilder('e')
            ->select('a.id, a.username, a.email')
            ->leftJoin('e.attendees', 'a')
            ->andWhere('e = :event')
            ->setParameter('event', $event)
            ->getQuery()
            ->getResult();

        if (count($result) == 1 && $result[0]['id'] == null) {
            return null;
        }

        return $result;
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

    public function getAllEventsUserIsAttending(User $user)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e.id')
            ->leftJoin('e.attendees', 'a')
            ->andWhere('a = :user')
            ->setParameter('user', $user);

        return $qb->getQuery()->getResult();
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
            ->select('e')
            ->leftJoin('e.attendees', 'a')
            ->andWhere('e.endsAt >= :now')
            ->groupBy('e.id')
            ->setParameters(array(
                'user' => $user,
                'now' => new \DateTime('now'),
            ));

        if ($this instanceof GroupEventRepository) {
            $qb->andWhere('e.deleted = 0');
        }

        if ($whereIsOrganizer) {
            $qb->andWhere('e.user = :user');
        } else {
            $qb->andWhere($qb->expr()->in('e.id', $subquery))
                ->andWhere('e.published = 1');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns list of past events that the user is registered for
     *
     * @param \Platformd\SpoutletBundle\Entity\User $user
     */
    public function getPastEventListForUser(User $user, $whereIsOrganizer = false)
    {
        $subquery = $this->createQueryBuilder('e2')
            ->select('e2.id')
            ->leftJoin('e2.attendees', 'a2')
            ->andWhere('a2 = :user')
            ->getDQL();

        $qb = $this->createQueryBuilder('e');

        $qb->select('e')
            ->leftJoin('e.attendees', 'a')
            ->andWhere('e.endsAt < :now')
            ->andWhere('e.published = 1')
            ->groupBy('e.id')
            ->setParameters(array(
                'user' => $user,
                'now' => new \DateTime('now'),
            ));

        if ($this instanceof GroupEventRepository) {
            $qb->andWhere('e.deleted = 0');
        }

        if ($whereIsOrganizer) {
            $qb->andWhere('e.user = :user');
        } else {
            $qb->andWhere($qb->expr()->in('e.id', $subquery))
                ->andWhere('e.published = 1');
        }

        if (method_exists($this, 'addActiveClauses')) {
            $this->addActiveClauses($qb);
        }

        return $qb->getQuery()->getResult();
    }

    public function findUpcomingEventsStartingDaysFromNow($days)
    {
        $daysOffset = abs((int)$days);

        $startDateTime = new \DateTime('+ '.$daysOffset.' days');
        $endDateTime = new \DateTime('+ '.$daysOffset.' days');

        $startDateTime->setTime(0, 0, 0);
        $endDateTime->setTime(23, 59, 59);

        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.startsAt >= :start')
            ->andWhere('e.startsAt <= :end')
            ->andWhere('e.published = 1')
            ->setParameters(array(
                'start' => $startDateTime,
                'end'   => $endDateTime,
            ));

        if ($this instanceof GroupEventRepository) {
            $qb->andWhere('e.deleted = 0');
        }

        return $qb->getQuery()
            ->getResult();
    }
}
