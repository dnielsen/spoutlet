<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;

class ContentReportRepository extends EntityRepository
{

    public function getContentReportTypeForAllSites($type)
    {

        if ($type != "GroupImage" && $type != "GroupVideo" && $type != "GroupNews" && $type != "Group" && $type != "GroupDiscussion" && $type != "GroupDiscussionPost") {
            throw new \Exception(sprintf("Unknown content report type = '%s'.", $type));
        }

        $qb = $this->getEntityManager()->createQuery(sprintf('
            SELECT item, COUNT(DISTINCT report.id) reportCount FROM SpoutletBundle:%s item
            LEFT JOIN item.contentReports report
            WHERE report.deleted = false
            GROUP BY item
            ORDER BY reportCount DESC, report.reportedAt
            ',
            $type));

        $sql = $qb->getSQL();

        return $qb
            ->execute();
    }

    public function getContentReportTypeForAllSitesArchived($type) {

        if ($type != "GroupImage" && $type != "GroupVideo" && $type != "GroupNews" && $type != "Group" && $type != "GroupDiscussion" && $type != "GroupDiscussionPost") {
            throw new \Exception(sprintf("Unknown content report type = '%s'.", $type));
        }

        return $this->getEntityManager()->createQuery(sprintf('
            SELECT item, COUNT(DISTINCT report.id) reportCount FROM SpoutletBundle:%s item
            LEFT JOIN item.contentReports report
            WHERE item.deleted = false AND
            report.deleted = true
            GROUP BY item
            HAVING reportCount > 0
            ORDER BY reportCount DESC, report.reportedAt
            ',
            $type))
            ->execute();
    }

    public function getContentReportTypeForAllSitesDeletedContent($type) {

        if ($type != "GroupImage" && $type != "GroupVideo" && $type != "GroupNews" && $type != "Group" && $type != "GroupDiscussion" && $type != "GroupDiscussionPost") {
            throw new \Exception(sprintf("Unknown content report type = '%s'.", $type));
        }

        $reason = "REPORTED_AND_REMOVED_BY_ADMIN";

        return $this->getEntityManager()->createQuery(sprintf('
            SELECT item, COUNT(DISTINCT report.id) reportCount FROM SpoutletBundle:%s item
            LEFT JOIN item.contentReports report
            WHERE item.deleted = true AND report.deleted = true
            GROUP BY item
            HAVING reportCount > 0
            ORDER BY reportCount DESC, report.reportedAt
            ',
            $type))
            ->execute();
    }

    public function getContentReportForAllSites()
    {

        return $this->getEntityManager()->createQuery('
            SELECT report, i, v, n, d, dp FROM SpoutletBundle:ContentReport report
            LEFT JOIN report.groupImage i
            LEFT JOIN report.groupVideo v
            LEFT JOIN report.groupNews n
            LEFT JOIN report.groupDiscussion d
            LEFT JOIN report.groupDiscussionPost dp
            WHERE report.deleted = false
            ORDER BY report.reportedAt
            ')
            ->execute();
    }

    public function getContentReportForSite($site)
    {

        return $this->getEntityManager()->createQuery('
            SELECT report, i, v, n, d, dp FROM SpoutletBundle:ContentReport report
            LEFT JOIN report.groupImage i
            LEFT JOIN report.groupVideo v
            LEFT JOIN report.groupNews n
            LEFT JOIN report.groupDiscussion d
            LEFT JOIN report.groupDiscussionPost dp
            JOIN report.site site
            WHERE report.deleted = false
            AND site = :site
            ORDER BY report.reportedAt
            ')
            ->setParameter('site', $site)
            ->execute();
    }

    public function deleteAllContentReportsForGroupNews($content) {

        $em = $this->getEntityManager();

        $reports = $em->createQuery('
            SELECT report, c FROM SpoutletBundle:ContentReport report
            LEFT JOIN report.groupNews c
            WHERE report.deleted = false
            AND c = :content
            ')
            ->setParameter('content', $content)
            ->execute();

        foreach ($reports as $report) {
            $report->setDeleted(true);
            $em->persist($report);
        }
    }

    public function deleteAllContentReportsForGroupImage($content) {

        $em = $this->getEntityManager();

        $reports = $em->createQuery('
            SELECT report, c FROM SpoutletBundle:ContentReport report
            LEFT JOIN report.groupImage c
            WHERE report.deleted = false
            AND c = :content
            ')
            ->setParameter('content', $content)
            ->execute();

        foreach ($reports as $report) {
            $report->setDeleted(true);
            $em->persist($report);
        }
    }

    public function deleteAllContentReportsForGroupVideo($content) {

        $em = $this->getEntityManager();

        $reports = $em->createQuery('
            SELECT report, c FROM SpoutletBundle:ContentReport report
            LEFT JOIN report.groupVideo c
            WHERE report.deleted = false
            AND c = :content
            ')
            ->setParameter('content', $content)
            ->execute();

        foreach ($reports as $report) {
            $report->setDeleted(true);
            $em->persist($report);
        }
    }

    public function deleteAllContentReportsForGroupDiscussion($content) {

        $em = $this->getEntityManager();

        $reports = $em->createQuery('
            SELECT report, d FROM SpoutletBundle:ContentReport report
            LEFT JOIN report.groupDiscussion d
            WHERE report.deleted = false
            AND d = :content
            ')
            ->setParameter('content', $content)
            ->execute();

        foreach ($reports as $report) {
            $report->setDeleted(true);
            $em->persist($report);
        }
    }

    public function deleteAllContentReportsForGroupDiscussionPost($content) {

        $em = $this->getEntityManager();

        $reports = $em->createQuery('
            SELECT report, dp FROM SpoutletBundle:ContentReport report
            LEFT JOIN report.groupDiscussionPost dp
            WHERE report.deleted = false
            AND dp = :content
            ')
            ->setParameter('content', $content)
            ->execute();

        foreach ($reports as $report) {
            $report->setDeleted(true);
            $em->persist($report);
        }
    }

    public function deleteAllContentReportsForGroup($content) {

        $em = $this->getEntityManager();

        $reports = $em->createQuery('
            SELECT report, c FROM SpoutletBundle:ContentReport report
            LEFT JOIN report.group c
            WHERE report.deleted = false
            AND c = :content
            ')
            ->setParameter('content', $content)
            ->execute();

        foreach ($reports as $report) {
            $report->setDeleted(true);
            $em->persist($report);
        }
    }

    public function getLastReportDateForUser($user)
    {
        $result = $this->createQueryBuilder('cr')
            ->where('cr.reporter = :user')
            ->setParameter('user', $user)
            ->orderBy('cr.reportedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->execute();

        return $result ? $result[0]->getReportedAt() : null;
    }
}
