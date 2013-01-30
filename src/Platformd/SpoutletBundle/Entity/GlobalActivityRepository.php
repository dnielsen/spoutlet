<?php

namespace Platformd\SpoutletBundle\Entity;

use Platformd\SpoutletBundle\Entity\Superclass\Activity,
    Platformd\SpoutletBundle\Entity\GlobalActivity
;

use Doctrine\ORM\EntityRepository,
    Doctrine\ORM\QueryBuilder
;

use DateTime,
    DateTimeZone
;

class GlobalActivityRepository extends EntityRepository
{
    /**
     * Returns number of new group members for a specific date range
     *
     * @param Group $group
     * @param \DateTime $start
     * @param \DateTime $thru
     * @return mixed
     */
    public function getCountNewGroupMembers(Group $group, DateTime $start = null, DateTime $thru = null)
    {
        $start = $start ? $start->modify('midnight') : new DateTime('today midnight', new DateTimeZone('UTC'));
        $thru = $thru ? $thru->modify('midnight') : new DateTime('tomorrow midnight', new DateTimeZone('UTC'));

        $qb = $this->createQueryBuilder('gA');
        $qb
            ->select('count(gA) as total')
            ->where('gA.object = :object')
            ->andWhere('gA.verb = :verb')
            ->andWhere('gA.objectId = :objectId')
            ->andWhere('gA.date > :start')
            ->andWhere('gA.date < :end')
            ->setParameter('object', Activity::OBJECT_GROUP)
            ->setParameter('verb', Activity::VERB_JOINED)
            ->setParameter('objectId', $group->getId())
            ->setParameter('start', $start)
            ->setParameter('end', $thru)
        ;

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Returns number of new discussions for a group and a specific date range
     *
     * @param Group $group
     * @param \DateTime $start
     * @param \DateTime $thru
     * @return mixed
     */
    public function getCountNewGroupDiscussions(Group $group, DateTime $start = null, DateTime $thru = null)
    {
        $start = $start ? $start->modify('midnight') : new DateTime('today midnight', new DateTimeZone('UTC'));
        $thru = $thru ? $thru->modify('midnight') : new DateTime('tomorrow midnight', new DateTimeZone('UTC'));

        $discussionIds = $this->getGroupDiscussionIds($group);

        if (count($discussionIds) > 0) {
            $qb = $this->_em->createQueryBuilder();
            $qb
                ->select('count(gA.id) as discussionCount')
                ->from($this->_entityName, 'gA')
                ->where('gA.object = :object')
                ->andWhere('gA.verb = :verb')
                ->andWhere('gA.date >= :start')
                ->andWhere('gA.date <= :end')
                ->andWhere($qb->expr()->in('gA.objectId', $discussionIds))
                ->setParameter('object', Activity::OBJECT_DISCUSSION)
                ->setParameter('verb', Activity::VERB_CREATED)
                ->setParameter('start', $start)
                ->setParameter('end', $thru)
            ;

            return $qb->getQuery()->getSingleScalarResult();
        }

        return 0;
    }

    /**
     * Returns number of discussions deleted for a group and a specific date range
     *
     * @param Group $group
     * @param \DateTime $start
     * @param \DateTime $thru
     * @return mixed
     */
    public function getCountDeletedGroupDiscussions(Group $group, DateTime $start = null, DateTime $thru = null)
    {
        $start = $start ? $start->modify('midnight') : new DateTime('today midnight', new DateTimeZone('UTC'));
        $thru = $thru ? $thru->modify('midnight') : new DateTime('tomorrow midnight', new DateTimeZone('UTC'));

        $discussionIds = $this->getGroupDiscussionIds($group);

        if (count($discussionIds) > 0) {
            $qb = $this->_em->createQueryBuilder();
            $qb
                ->select('count(gA.id) as discussionCount')
                ->from($this->_entityName, 'gA')
                ->where('gA.object = :object')
                ->andWhere('gA.verb = :verb')
                ->andWhere('gA.date > :start')
                ->andWhere('gA.date < :end')
                ->andWhere($qb->expr()->in('gA.objectId', $discussionIds))
                ->setParameter('object', Activity::OBJECT_DISCUSSION)
                ->setParameter('verb', Activity::VERB_DELETED)
                ->setParameter('start', $start)
                ->setParameter('end', $thru)
            ;

            return $qb->getQuery()->getSingleScalarResult();
        }

        return 0;
    }

    /**
     * Returns number of views for a group discussion within a specific date range
     *
     * @param Group $group
     * @param \DateTime $start
     * @param \DateTime $thru
     * @return mixed
     */
    public function getCountGroupDiscussionViews(GroupDiscussion $groupDiscussion, DateTime $start = null, DateTime $thru = null)
    {
        $start = $start ? $start->modify('midnight') : new DateTime('today midnight', new DateTimeZone('UTC'));
        $thru = $thru ? $thru->modify('midnight') : new DateTime('tomorrow midnight', new DateTimeZone('UTC'));

        $qb = $this->_em->createQueryBuilder();
        $qb
            ->select('count(gA.id) as discussionPostCount')
            ->from($this->_entityName, 'gA')
            ->where('gA.object = :object')
            ->andWhere('gA.verb = :verb')
            ->andWhere('gA.date > :start')
            ->andWhere('gA.date < :end')
            ->andWhere('gA.objectId = :discussionId')
            ->setParameter('object', Activity::OBJECT_DISCUSSION)
            ->setParameter('verb', Activity::VERB_VIEWED)
            ->setParameter('start', $start)
            ->setParameter('end', $thru)
            ->setParameter('discussionId', $groupDiscussion->getId())
        ;

        return $qb->getQuery()->getSingleScalarResult();

    }

    /**
     * Returns number of posts created for a group discussion within a specific date range
     *
     * @param Group $group
     * @param \DateTime $start
     * @param \DateTime $thru
     * @return mixed
     */
    public function getCountGroupDiscussionReplies(GroupDiscussion $groupDiscussion, DateTime $start = null, DateTime $thru = null)
    {
        $start = $start ? $start->modify('midnight') : new DateTime('today midnight', new DateTimeZone('UTC'));
        $thru = $thru ? $thru->modify('midnight') : new DateTime('tomorrow midnight', new DateTimeZone('UTC'));

        $discussionPostIds = $this->getGroupDiscussionPostIds($groupDiscussion);

        if (count($discussionPostIds) > 0) {
            $qb = $this->_em->createQueryBuilder();
            $qb
                ->select('count(gA.id) as discussionPostCount')
                ->from($this->_entityName, 'gA')
                ->where('gA.object = :object')
                ->andWhere('gA.verb = :verb')
                ->andWhere('gA.date > :start')
                ->andWhere('gA.date < :end')
                ->andWhere($qb->expr()->in('gA.objectId', $discussionPostIds))
                ->setParameter('object', Activity::OBJECT_DISCUSSION_POST)
                ->setParameter('verb', Activity::VERB_CREATED)
                ->setParameter('start', $start)
                ->setParameter('end', $thru)
            ;

            return $qb->getQuery()->getSingleScalarResult();
        }

        return 0;
    }

    /**
     * Returns number of active users for a group discussion within a specific date range
     *
     * @param Group $group
     * @param \DateTime $start
     * @param \DateTime $thru
     * @return mixed
     */
    public function getCountGroupDiscussionActiveUsers(GroupDiscussion $groupDiscussion, DateTime $start = null, DateTime $thru = null)
    {
        $start = $start ? $start->modify('midnight') : new DateTime('today midnight', new DateTimeZone('UTC'));
        $thru = $thru ? $thru->modify('midnight') : new DateTime('tomorrow midnight', new DateTimeZone('UTC'));

        $discussionPostIds = $this->getGroupDiscussionPostIds($groupDiscussion);

        if (count($discussionPostIds) > 0) {
            $qb = $this->_em->createQueryBuilder();
            $qb
                ->select('COUNT(DISTINCT gA.subjectId) as activeUsersCount')
                ->from($this->_entityName, 'gA')
                ->where('gA.object = :object')
                ->andWhere('gA.verb = :verb')
                ->andWhere('gA.date > :start')
                ->andWhere('gA.date < :end')
                ->andWhere($qb->expr()->in('gA.objectId', $discussionPostIds))
                ->setParameter('object', Activity::OBJECT_DISCUSSION_POST)
                ->setParameter('verb', Activity::VERB_CREATED)
                ->setParameter('start', $start)
                ->setParameter('end', $thru)
                ->groupBy('gA.subjectId')
            ;

            $result = $qb->getQuery()->getResult();

            if (count($result) > 0) {
                return $result[0]['activeUsersCount'];
            }
        }

        return 0;
    }

    /**
     * Returns first logged activity date, a.k.a the origin of times
     */
    public function getOriginOfTimes()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb
            ->select('gA')
            ->from($this->_entityName, 'gA')
            ->orderBy('gA.date', 'ASC')
            ->setMaxResults(1)
        ;

        $result = $qb->getQuery()->getOneOrNullResult();
        $date = clone $result->getDate();

        if ($result) {
            return $date;
        } else {
            return new DateTime('today midnight', new DateTimeZone('UTC'));
        }
    }

    /**
     * Returns all discussion Ids for a specific group
     *
     * @param Group $group
     * @return array
     */
    private function getGroupDiscussionIds(Group $group)
    {
        $discussionIds = array();

        $qb = $this->_em->createQueryBuilder();
        $qb
            ->select('gD.id')
            ->from('SpoutletBundle:GroupDiscussion', 'gD')
            ->where('gD.group = :group')
            ->setParameter('group', $group)
        ;

        $results = $qb->getQuery()->getResult();

        foreach ($results as $result) {
            $discussionIds[] = $result['id'];
        }

        return $discussionIds;
    }

    /**
     * Returns all discussion post Ids for a specific discussion
     *
     * @param Group $group
     * @return array
     */
    private function getGroupDiscussionPostIds(GroupDiscussion $groupDiscussion)
    {
        $discussionPostIds = array();

        $qb = $this->_em->createQueryBuilder();
        $qb
            ->select('gDP.id')
            ->from('SpoutletBundle:GroupDiscussionPost', 'gDP')
            ->where('gDP.groupDiscussion = :groupDiscussion')
            ->setParameter('groupDiscussion', $groupDiscussion)
        ;

        $results = $qb->getQuery()->getResult();

        foreach ($results as $result) {
            $discussionPostIds[] = $result['id'];
        }

        return $discussionPostIds;
    }
}
