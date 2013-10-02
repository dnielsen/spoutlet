<?php

namespace Platformd\EventBundle\Repository;

use Doctrine\ORM\EntityManager,
    Doctrine\ORM\EntityRepository
;

use Platformd\EventBundle\Entity\Event;
use Platformd\EventBundle\Entity\GroupEventRepository;
use Platformd\SpoutletBundle\Entity\MassEmail;
use Platformd\EventBundle\Entity\EventRsvpAction;
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

        # this is what i'd like to have...
/*        '
        SELECT
            f0_.id AS id0,
            f0_.username AS username1,
            f0_.email AS email2,
            SUM(CASE WHEN g1_.attendance = 'ATTENDING_YES' THEN 1 ELSE 0 END) `yes`,
            SUM(CASE WHEN g1_.attendance = 'ATTENDING_NO' THEN 1 ELSE 0 END) `no`
        FROM
            global_event g2_
        LEFT JOIN global_events_attendees g4_ ON g2_.id = g4_.globalevent_id
        LEFT JOIN fos_user f3_ ON f3_.id = g4_.user_id
        LEFT JOIN global_event_rsvp_actions g1_ ON g2_.id = g1_.event_id
        LEFT JOIN fos_user f0_ ON g1_.user_id = f0_.id
        WHERE
            g2_.id = 1
        HAVING `yes` > `no`'*/

        $ids = array(0);

        $attendees = $this->createQueryBuilder('e')
            ->select('a.id')
            ->leftJoin('e.attendees', 'a')
            ->where('e = :event')
            ->setParameter('event', $event)
            ->getQuery()
            ->getArrayResult();

        foreach ($attendees as $attendee) {
            array_push($ids, $attendee['id']);
        }

        $result = $this->createQueryBuilder('e')
            ->select('u.id, u.username, u.email, MAX(rsvp.rsvpAt) rsvpAt')
            ->leftJoin('e.attendees', 'a')
            ->leftJoin('e.rsvpActions', 'rsvp')
            ->leftJoin('rsvp.user', 'u')
            ->where('e = :event')
            ->andWhere('u.id in (:attendees)')
            ->andWhere('rsvp.attendance = :attendance')
            ->setParameter('event', $event)
            ->setParameter('attendees', $ids)
            ->setParameter('attendance', EventRsvpAction::ATTENDING_YES)
            ->groupBy('u.id')
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
     * @param \Platformd\SpoutletBundle\Entity\MassEmail $email
     */
    public function saveEmail(MassEmail $email)
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

    public function findUpcomingEventsForSiteLimited($site, $limit, $published)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e', 's')
            ->leftJoin('e.sites', 's')
            ->where('e.endsAt >= :now')
            ->andWhere('e.published = :published')
            ->andWhere('e.active = :published')
            ->andWhere('s = :site')
            ->orderBy('e.startsAt', 'ASC')
            ->setParameter('now', new DateTime())
            ->setParameter('published', $published)
            ->setParameter('site', $site)
            ->setMaxResults($limit)
        ;

        return $qb->getQuery()->getResult();
    }
}
