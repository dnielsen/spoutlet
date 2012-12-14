<?php

namespace Platformd\SpoutletBundle\Entity;

use Platformd\SpoutletBundle\Entity\Group;
use Platformd\SpoutletBundle\Entity\GroupMembershipAction;
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
            SELECT g FROM SpoutletBundle:Group g
            LEFT JOIN g.members m
            WHERE g.deleted = false
            AND m.id = :userId
            ORDER BY g.name')
            ->setParameter('userId', $user->getId())
            ->execute();
    }

    public function getGroupAndMemberCountByRegion() {

        return $this->getEntityManager()->createQuery('
            SELECT s.name region, COUNT(DISTINCT g.id) groups, COUNT(DISTINCT m.id) members FROM SpoutletBundle:Group g
            LEFT JOIN g.sites s
            LEFT JOIN g.members m
            WHERE g.deleted = false
            GROUP BY s.id')
            ->execute();
    }

    public function findAllGroupsRelevantForSite($site) {

        return $this->getEntityManager()->createQuery('
            SELECT g FROM SpoutletBundle:Group g
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
            ->leftJoin('g.sites', 's')
            ->where('g.category = :category')
            ->andWhere('(s = :site OR g.allLocales = true)')
            ->andWhere('g.deleted = false')
            ->setParameter('category', $category)
            ->setParameter('site', $site)
            ->orderBy('g.createdAt', 'DESC');

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

    public function findGroups($groupName, $category, $status, $sites, $startDate="", $endDate="")
    {
        return $this->getFindGroupsQB($groupName, $category, $status, $sites, $startDate, $endDate)
            ->getQuery()
            ->execute()
        ;
    }

    public function getGroupMemberListForExport($groupId)
    {
        $qb = $this->createQueryBuilder('g')
            ->leftJoin('g.members', 'm')
            ->select('m.username', 'm.id', 'm.firstname', 'm.lastname', 'm.email', 'g.name', 'm.country', '(SELECT MAX(a.createdAt) FROM SpoutletBundle:GroupMembershipAction a WHERE a.group = g AND a.user = m.id)')
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
            ->select('g','a.username', 'a.id', 'a.firstname', 'a.lastname', 'a.email', 'g.name', 'a.country', 'v', 'v.title', 'v.createdAt', 'c', '(SELECT MAX(ma.createdAt) FROM SpoutletBundle:GroupMembershipAction ma WHERE ma.group = g AND ma.user = a.id)')
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
            ->select('g', 'a.username', 'a.id', 'a.firstname', 'a.lastname', 'a.email', 'g.name', 'a.country', 'i.title', 'i.createdAt', 'c', 'i', '(SELECT MAX(ma.createdAt) FROM SpoutletBundle:GroupMembershipAction ma WHERE ma.group = g AND ma.user = a.id)')
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
            ->select('g', 'a.username', 'a.id', 'a.firstname', 'a.lastname', 'a.email', 'g.name', 'a.country', 'n', 'n.title', 'n.createdAt', 'c', '(SELECT MAX(ma.createdAt) FROM SpoutletBundle:GroupMembershipAction ma WHERE ma.group = g AND ma.user = a.id)')
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

    public function findGroupStats(array $filters = array())
    {
        $filters = array_merge(
            array('groupName' => '', 'catagory' => '', 'deleted' => '', 'sites' => array(), 'startDate' => '', 'endDate' => ''),
            $filters
        );
        $qb = $this->getFindGroupsQB($filters['groupName'], $filters['category'], $filters['deleted'], $filters['sites'], $filters['startDate'], $filters['endDate'])
            ->addSelect('n', 'v', 'i', 'm', 'o', 'members')
            ->leftJoin('g.newsArticles', 'n')
            ->leftJoin('g.videos', 'v')
            ->leftJoin('g.images', 'i')
            ->leftJoin('g.owner', 'o')
            ->leftJoin('g.members', 'members')
            ->leftJoin('g.membershipActions', 'm')
        ;

        return $qb->getQuery()->execute();
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
            ->leftJoin('g.sites', 's')
            ->where('g.featured = true')
            ->andWhere('(s = :site OR g.allLocales = true)')
            ->andWhere('g.deleted = false')
            ->addOrderBy('g.featuredAt', 'DESC')
            ->distinct('g.id')
            ->setMaxResults(4)
            ->setParameter('site', $site);

        return $qb->getQuery()->execute();
    }
    public function findGroupsForFacebookLikesLastUpdatedAt($minutes)
    {
        $date = new \DateTime;
        $date->modify(sprintf('-%d sec', $minutes));

        $qb = $this->createQueryBuilder('g')
            ->addSelect('s')
            ->leftJoin('g.sites', 's')
            ->addOrderBy('g.facebookLikesUpdatedAt', 'ASC')
            ->andWhere('g.facebookLikesUpdatedAt >= :date')
            ->setParameter('date', $date);

        return $qb->getQuery()->execute();
    }
}
