<?php

namespace Platformd\GroupBundle\Entity;

use Platformd\GroupBundle\Entity\Group;
use Platformd\GroupBundle\Entity\GroupMembershipAction;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use DateTime;
use DateTimeZone;

/**
 * GroupsRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class GroupRepository extends EntityRepository
{
    public function getAllGroupsForUser($user) {
        return $this->getEntityManager()->createQuery('
            SELECT g FROM GroupBundle:Group g
            LEFT JOIN g.members m
            WHERE g.deleted = false
            AND m.id = :userId
            ORDER BY g.name')
            ->setParameter('userId', $user->getId())
            ->execute();
    }

    public function getAllGroupsForUserAndSite($user, $site)
    {
        return $this->createQueryBuilder('g')
            ->select('g, COUNT(DISTINCT m.id) memberCount')
            ->leftJoin('g.members', 'm')
            ->leftJoin('g.sites', 's')
            ->andWhere('g.deleted = false')
            ->andWhere('m.id = :userId')
            ->andWhere('s.id = :siteId')
            ->orderBy('g.name')
            ->setParameters(array(
                'userId' => $user->getId(),
                'siteId' => $site->getId()
            ))
            ->groupBy('g.id')
            ->getQuery()
            ->execute();
    }

    public function getGroupAndMemberCountByRegion() {

        return $this->getEntityManager()->createQuery('
            SELECT s.name region, COUNT(DISTINCT g.id) groups, COUNT(DISTINCT m.id) members FROM GroupBundle:Group g
            LEFT JOIN g.sites s
            LEFT JOIN g.members m
            WHERE g.deleted = false
            GROUP BY s.id')
            ->execute();
    }

    public function findAllGroupsRelevantForSite($site) {

        return $this->getEntityManager()->createQuery('
            SELECT g FROM GroupBundle:Group g
            LEFT JOIN g.sites s
            WHERE g.deleted = false
            AND (g.allLocales = true OR s = :site)')
            ->setParameter('site', $site)
            ->execute();
    }

    public function findGroupsByName($groupName)
    {
        $qb = $this->createQueryBuilder('g')
            ->where('g.name like :groupName')
            ->andWhere('g.deleted = false')
            ->setParameter('groupName', '%'.$groupName.'%');

        return $qb->getQuery()->execute();
    }

    public function findGroupsByNameAndSite($groupName, $site)
    {
        $qb = $this->createQueryBuilder('g')
            ->leftJoin('g.sites', 's')
            ->where('g.name like :groupName')
            ->andWhere('(s = :site OR g.allLocales = true)')
            ->andWhere('g.deleted = false')
            ->setParameter('groupName', '%'.$groupName.'%')
            ->setParameter('site', $site);

        return $qb->getQuery()->execute();
    }

    public function findGroupsByCategoryAndSite($category, $site)
    {
        $qb = $this->createQueryBuilder('g')
            ->select('g, COUNT(DISTINCT m.id) memberCount')
            ->leftJoin('g.members', 'm')
            ->leftJoin('g.sites', 's')
            ->where('g.category = :category')
            ->andWhere('(s = :site OR g.allLocales = true)')
            ->andWhere('g.deleted = false')
            ->andWhere('g.featured <> 1')
            ->setParameter('category', $category)
            ->setParameter('site', $site)
            ->orderBy('g.createdAt', 'DESC')
            ->groupBy('g.id');

        return $qb->getQuery()->execute();
    }

    public function getFindGroupsQB($groupName, $category, $status, $sites, $startDate="", $endDate="")
    {
        $qb = $this->createQueryBuilder('g')
            ->leftJoin('g.sites', 's');

        if (count($sites) > 0) {

            $qb->andWhere('(s.defaultLocale IN (:siteList) OR g.allLocales = true)');
            $qb->setParameter('siteList', $sites);

        }

        if ($groupName) {
            $qb->andWhere('g.name like :groupName');
            $qb->setParameter('groupName', '%'.$groupName.'%');
        }

        if ($category != "") {
            $qb->andWhere('g.category = :category');
            $qb->setParameter('category', $category);
        }

        if ($status != "") {
            $qb->andWhere('g.deleted = :status');
            $qb->setParameter('status', $status);
        }

        if ($startDate != "") {

            $startDate->setTime(0, 0, 0);
            $qb->andWhere('g.createdAt >= :startDate');
            $qb->setParameter('startDate', $startDate);
        }

        if ($endDate != "") {

            $endDate->setTime(23, 59, 59);
            $qb->andWhere('g.createdAt <= :endDate');
            $qb->setParameter('endDate', $endDate);
        }

        $qb->distinct('g.id');

        return $qb;
    }

    public function findGroups(array $filters)
    {
        return $this->getFindGroupsQB($filters['groupName'], $filters['category'], $filters['deleted'], $filters['sites'], $filters['startDate'], $filters['endDate'])
            ->getQuery()
            ->execute()
        ;
    }

    public function getGroupMemberListForExport($groupId)
    {
        $qb = $this->createQueryBuilder('g')
            ->leftJoin('g.members', 'm')
            ->select('m.username', 'm.id', 'm.firstname', 'm.lastname', 'm.email', 'g.name', 'm.country', '(SELECT MAX(a.createdAt) FROM GroupBundle:GroupMembershipAction a WHERE a.group = g AND a.user = m.id)')
            ->where('g.id = :groupId')
            ->setParameter('groupId', $groupId);

        return $qb->getQuery()->execute();
    }

    public function getGroupVideosForExport($groupId)
    {
        $qb = $this->createQueryBuilder('g')
            ->leftJoin('g.videos', 'v')
            ->leftJoin('v.author', 'a')
            ->leftJoin('v.contentReports', 'c')
            ->select('g','a.username', 'a.id', 'a.firstname', 'a.lastname', 'a.email', 'g.name', 'a.country', 'v', 'v.title', 'v.createdAt', 'c', '(SELECT MAX(ma.createdAt) FROM GroupBundle:GroupMembershipAction ma WHERE ma.group = g AND ma.user = a.id)')
            ->where('g.id = :groupId')
            ->setParameter('groupId', $groupId);

        $result = $qb->getQuery()->execute();

        if (!$result || count($result) < 1) {
            return null;
        }

        if (!$result[0][0] || $result[0][0]->getId() < 1) {
            return null;
        }

        return $result[0][0];
    }

    public function getGroupImagesForExport($groupId)
    {
        $qb = $this->createQueryBuilder('g')
            ->leftJoin('g.images', 'i')
            ->leftJoin('i.author', 'a')
            ->leftJoin('i.contentReports', 'c')
            ->select('g', 'a.username', 'a.id', 'a.firstname', 'a.lastname', 'a.email', 'g.name', 'a.country', 'i.title', 'i.createdAt', 'c', 'i', '(SELECT MAX(ma.createdAt) FROM GroupBundle:GroupMembershipAction ma WHERE ma.group = g AND ma.user = a.id)')
            ->where('g.id = :groupId')
            ->setParameter('groupId', $groupId);

        $result = $qb->getQuery()->execute();

        if (!$result || count($result) < 1) {
            return null;
        }

        if (!$result[0][0] || $result[0][0]->getId() < 1) {
            return null;
        }

        return $result[0][0];
    }

    public function getGroupNewsArticlesForExport($groupId)
    {
        $qb = $this->createQueryBuilder('g')
            ->leftJoin('g.newsArticles', 'n')
            ->leftJoin('n.author', 'a')
            ->leftJoin('n.contentReports', 'c')
            ->select('g', 'a.username', 'a.id', 'a.firstname', 'a.lastname', 'a.email', 'g.name', 'a.country', 'n', 'n.title', 'n.createdAt', 'c', '(SELECT MAX(ma.createdAt) FROM GroupBundle:GroupMembershipAction ma WHERE ma.group = g AND ma.user = a.id)')
            ->where('g.id = :groupId')
            ->setParameter('groupId', $groupId);

        $result = $qb->getQuery()->execute();

        if (!$result || count($result) < 1) {
            return null;
        }

        if (!$result[0][0] || $result[0][0]->getId() < 1) {
            return null;
        }

        return $result[0][0];
    }

    public function getGroupDiscussionsForExport($groupId)
    {
        $qb = $this->createQueryBuilder('g')
            ->leftJoin('g.discussions', 'd')
            ->leftJoin('d.author', 'a')
            ->leftJoin('d.contentReports', 'c')
            ->select('g', 'a.username', 'a.id', 'a.firstname', 'a.lastname', 'a.email', 'g.name', 'a.country', 'd', 'd.title', 'd.createdAt', 'c', '(SELECT MAX(ma.createdAt) FROM GroupBundle:GroupMembershipAction ma WHERE ma.group = g AND ma.user = a.id)')
            ->where('g.id = :groupId')
            ->setParameter('groupId', $groupId);

        $result = $qb->getQuery()->execute();

        if (!$result || count($result) < 1) {
            return null;
        }

        if (!$result[0][0] || $result[0][0]->getId() < 1) {
            return null;
        }

        return $result[0][0];
    }

    public function findGroupStatsQB(array $filters = array())
    {
        $filters = array_merge(
            array('groupName' => '', 'category' => '', 'deleted' => '', 'sites' => array(), 'startDate' => '', 'endDate' => ''),
            $filters
        );
        $qb = $this->getFindGroupsQB($filters['groupName'], $filters['category'], $filters['deleted'], $filters['sites'], $filters['startDate'], $filters['endDate'])
            ->addSelect('m', 'o', 's')
            ->leftJoin('g.owner', 'o')
            ->leftJoin('g.membershipActions', 'm')
            ->groupBy('g.id')
            ->distinct(false)
        ;

        return $qb;
    }

    public function findGroupMediaCountsIn($ids)
    {
        if (count($ids) > 0) {

            $return = array();

            $videos = $this->createQueryBuilder('g')
                ->select('g.id', 'COUNT(v.id) videoCount')
                ->leftJoin('g.videos', 'v')
                ->andWhere('g.id IN (:ids)')
                ->groupBy('g.id')
                ->setParameter('ids', $ids)
                ->getQuery()
                ->execute();

            foreach($videos as $groupVideoInfo) {
                $return[$groupVideoInfo['id']]['videoCount'] = $groupVideoInfo['videoCount'];
            }

            $news = $this->createQueryBuilder('g')
                ->select('g.id', 'COUNT(n.id) newsCount')
                ->leftJoin('g.newsArticles', 'n')
                ->andWhere('g.id IN (:ids)')
                ->groupBy('g.id')
                ->setParameter('ids', $ids)
                ->getQuery()
                ->execute();

            foreach($news as $groupNewsInfo) {
                $return[$groupNewsInfo['id']]['newsCount'] = $groupNewsInfo['newsCount'];
            }

            $images = $this->createQueryBuilder('g')
                ->select('g.id', 'COUNT(i.id) imageCount')
                ->leftJoin('g.images', 'i')
                ->andWhere('g.id IN (:ids)')
                ->groupBy('g.id')
                ->setParameter('ids', $ids)
                ->getQuery()
                ->execute();

            foreach($images as $groupImageInfo) {
                $return[$groupImageInfo['id']]['imageCount'] = $groupImageInfo['imageCount'];
            }

            return $return;
        }

        return array();
    }

    public function findGroupMemberCountsIn($ids)
    {
        if (count($ids) > 0) {
            $qb = $this->createQueryBuilder('g')
                ->select('g.id', 'COUNT(m.id) as membercount')
                ->leftJoin('g.members', 'm')
                ->andWhere('g.id IN (:ids)')
                ->setParameter('ids', $ids)
                ->groupBy('g.id');

            return $qb->getQuery()->execute();
        }

        return array();
    }

    public function findGroupStats(array $filters = array())
    {
        return $this->findGroupStatsQB($filters)->getQuery()->execute();
    }

    public function findMostRecentlyCreatedGroupsForSite($site, $limit=8)
    {
        $qb = $this->createQueryBuilder('g')
            ->leftJoin('g.sites', 's')
            ->andWhere('(s = :site OR g.allLocales = true)')
            ->andWhere('g.deleted = false')
            ->addOrderBy('g.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('site', $site);

        return $qb->getQuery()->execute();
    }

    public function findMostPopularGroupsForSite($site, $limit=8)
    {
        $query = '
        SELECT
            pd_groups.`id` AS group_id,
            pd_groups.`name` AS group_name,
            pd_group_site.group_id,
            pd_groups.slug,
            (
                SELECT
                    COUNT(*)
                FROM
                    pd_groups_members
                WHERE
                    pd_groups.id = group_id
            ) AS member_count
        FROM
            pd_groups
        INNER JOIN pd_group_site ON pd_group_site.group_id = pd_groups.id
        WHERE
            pd_group_site.site_id = :site
        AND pd_groups.deleted = 0
        ORDER BY
            member_count DESC
        LIMIT 8';

        $stmt = $this->getEntityManager()
                     ->getConnection()
                     ->prepare($query);

        $stmt->bindValue('site', $site->getId());

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getGroupMembers($id)
    {
        $query = '
        SELECT DISTINCT
            fos_user.username,
            pd_group_membership_actions.action,
            Max(
                pd_group_membership_actions.created_at
            ) AS created_at,
            fos_user.id
        FROM
        pd_groups
        INNER JOIN pd_group_membership_actions ON pd_groups.id = pd_group_membership_actions.group_id
        INNER JOIN fos_user ON fos_user.id = pd_group_membership_actions.user_id
        INNER JOIN pd_groups_members ON pd_group_membership_actions.user_id = pd_groups_members.user_id
        WHERE
            pd_group_membership_actions.group_id = :id
        AND
            pd_groups_members.user_id IN (select user_id from pd_group_membership_actions)
        GROUP BY pd_group_membership_actions.user_id';

        $stmt = $this->getEntityManager()
                     ->getConnection()
                     ->prepare($query);

        $stmt->bindValue('id', $id);

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findAllFeaturedGroupsForSite($site)
    {
        $qb = $this->createQueryBuilder('g')
            ->select('g, COUNT(DISTINCT m.id) memberCount')
            ->leftJoin('g.sites', 's')
            ->leftJoin('g.members', 'm')
            ->where('g.featured = true')
            ->andWhere('(s = :site OR g.allLocales = true)')
            ->andWhere('g.deleted = false')
            ->addOrderBy('g.featuredAt', 'DESC')
            ->distinct('g.id')
            ->setMaxResults(4)
            ->setParameter('site', $site)
            ->groupBy('g.id');

        return $qb->getQuery()->execute();
    }

    public function findGroupsForFacebookLikesLastUpdatedAt($minutes)
    {
        $date = new \DateTime;
        $date->modify(sprintf('-%d sec', $minutes));

        $qb = $this->createQueryBuilder('g')
            ->addOrderBy('g.facebookLikesUpdatedAt', 'ASC')
            ->addOrderBy('g.id', 'ASC')
            ->andWhere('g.facebookLikesUpdatedAt <= :date OR g.facebookLikesUpdatedAt IS NULL')
            ->setParameter('date', $date);

        return $qb->getQuery()->iterate();
    }

    public function findAllGroupsWhereIdInForSite($groupIds, $site)
    {
        $qb = $this->createQueryBuilder('g');
        return $qb->leftJoin('g.sites', 's')
            ->where($qb->expr()->in('g.id', $groupIds))
            ->andWhere('g.deleted = false')
            ->andWhere('(s = :site OR g.allLocales = true)')
            ->setParameter('site', $site)
            ->getQuery()
            ->execute();
    }

    public function findAllGroupsWhereIdNotInForSite($groupIds, $site)
    {
        $qb = $this->createQueryBuilder('g');
        return $qb->leftJoin('g.sites', 's')
            ->where($qb->expr()->notIn('g.id', $groupIds))
            ->andWhere('g.deleted = false')
            ->andWhere('(s = :site OR g.allLocales = true)')
            ->setParameter('site', $site)
            ->getQuery()
            ->execute();
    }

    public function findAllOwnedGroupsForContest($user, $entry, $site)
    {
        $groupIds = array(0);

        foreach($entry->getGroups() as $group) {
            array_push($groupIds, $group->getId());
        }

        $qb = $this->createQueryBuilder('g');

        return $qb->leftJoin('g.sites', 's')
            ->where('g.owner = :user')
            ->andWhere('g.deleted = false')
            ->andWhere('(s = :site OR g.allLocales = true)')
            ->andWhere($qb->expr()->notIn('g.id', $groupIds))
            ->setParameter('user', $user)
            ->setParameter('site', $site)
            ->getQuery()
            ->execute();
    }

    public function findGroupWinnersForContest($contest)
    {
        $ids = count($contest->getWinners()) > 0 ? $contest->getWinners() : array(0);
        $qb  = $this->createQueryBuilder('g');

        return $qb->where($qb->expr()->in('g.id', $ids))
            ->getQuery()
            ->execute();
    }

    public function getMembershipCountByGroup($group)
    {
        return $this->createQueryBuilder('g')
            ->select('COUNT(m.id) as membershipCount')
            ->leftJoin('g.members', 'm')
            ->where('g.id = :groupId')
            ->setParameter('groupId', $group->getId())
            ->getQuery()
            ->execute();
    }

    public function isUserMemberOfGroup($user, $group)
    {
        return $this->createQueryBuilder('g')
            ->select('COUNT(m.id) as isMember')
            ->leftJoin('g.members', 'm')
            ->andWhere('m.id = :userId')
            ->andWhere('g.id = :groupId')
            ->setParameter('userId', $user->getId())
            ->setParameter('groupId', $group->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }
}
