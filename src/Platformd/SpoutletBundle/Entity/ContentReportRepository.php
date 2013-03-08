<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Platformd\SpoutletBundle\Entity\ContentReport;

class ContentReportRepository extends EntityRepository
{
    private static $validTypes = array(
        'GroupEvent',
        'GroupImage',
        'GroupNews',
        'GroupVideo',
        'Group',
        'Comment',
        'GalleryMedia',
        'GroupDiscussion',
        'GroupDiscussionPost',
    );

    private static $typeToBundleMap = array(
        'GroupImage'            => 'GroupBundle',
        'GroupNews'             => 'GroupBundle',
        'GroupVideo'            => 'GroupBundle',
        'Group'                 => 'GroupBundle',
        'Comment'               => 'SpoutletBundle',
        'GalleryMedia'          => 'SpoutletBundle',
        'GroupDiscussion'       => 'GroupBundle',
        'GroupDiscussionPost'   => 'GroupBundle',
        'GroupEvent'            => 'EventBundle',
    );

    public function getBundleFromType($type)
    {
        return self::$typeToBundleMap[$type];
    }

    public function getContentReportTypeForAllSites($type)
    {

        if (!in_array($type, self::$validTypes)) {
            throw new \Exception(sprintf("Unknown content report type = '%s'.", $type));
        }

        return $this->getEntityManager()->createQuery(sprintf('
            SELECT item, COUNT(DISTINCT report.id) reportCount, site.defaultLocale locale, site.fullDomain fullDomain FROM %s:%s item
            LEFT JOIN item.contentReports report
            LEFT JOIN report.site site
            WHERE report.deleted = false
            GROUP BY item
            ORDER BY reportCount DESC, report.reportedAt
            ',
            $this->getBundleFromType($type),
            $type))
            ->execute();
    }

    public function getContentReportTypeForAllSitesArchived($type) {

        if (!in_array($type, self::$validTypes)) {
            throw new \Exception(sprintf("Unknown content report type = '%s'.", $type));
        }

        return $this->getEntityManager()->createQuery(sprintf('
            SELECT item, COUNT(DISTINCT report.id) reportCount, site.defaultLocale locale, site.fullDomain fullDomain FROM %s:%s item
            LEFT JOIN item.contentReports report
            LEFT JOIN report.site site
            WHERE item.deleted = false AND
            report.deleted = true
            GROUP BY item
            HAVING reportCount > 0
            ORDER BY reportCount DESC, report.reportedAt
            ',
            $this->getBundleFromType($type),
            $type))
            ->execute();
    }

    public function getContentReportTypeForAllSitesDeletedContent($type) {

        if (!in_array($type, self::$validTypes)) {
            throw new \Exception(sprintf("Unknown content report type = '%s'.", $type));
        }

        $reason = "REPORTED_AND_REMOVED_BY_ADMIN";

        return $this->getEntityManager()->createQuery(sprintf('
            SELECT item, COUNT(DISTINCT report.id) reportCount, site.defaultLocale locale, site.fullDomain fullDomain FROM %s:%s item
            LEFT JOIN item.contentReports report
            LEFT JOIN report.site site
            WHERE item.deleted = true AND report.deleted = true
            GROUP BY item
            HAVING reportCount > 0
            ORDER BY reportCount DESC, report.reportedAt
            ',
            $this->getBundleFromType($type),
            $type))
            ->execute();
    }

    public function getContentReportForAllSites()
    {

        return $this->getEntityManager()->createQuery('
            SELECT report, i, v, n, g FROM SpoutletBundle:ContentReport report
            LEFT JOIN report.groupImage i
            LEFT JOIN report.groupVideo v
            LEFT JOIN report.groupNews n
            LEFT JOIN report.galleryMedia g
            WHERE report.deleted = false
            ORDER BY report.reportedAt
            ')
            ->execute();
    }

    public function getContentReportForSite($site)
    {

        return $this->getEntityManager()->createQuery('
            SELECT report, i, v, n, g FROM SpoutletBundle:ContentReport report
            LEFT JOIN report.groupImage i
            LEFT JOIN report.groupVideo v
            LEFT JOIN report.groupNews n
            LEFT JOIN report.galleryMedia g
            JOIN report.site site
            WHERE report.deleted = false
            AND site = :site
            ORDER BY report.reportedAt
            ')
            ->setParameter('site', $site)
            ->execute();
    }

    public function deleteAllContentReportsForGroupEvent($content) {

        $em = $this->getEntityManager();

        $reports = $em->createQuery('
            SELECT report, c FROM SpoutletBundle:ContentReport report
            LEFT JOIN report.groupEvent c
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


    public function deleteAllContentReportsForGalleryMedia($content) {

        $em = $this->getEntityManager();

        $reports = $em->createQuery('
            SELECT report, c FROM SpoutletBundle:ContentReport report
            LEFT JOIN report.galleryMedia c
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

    public function deleteAllContentReportsForComment($content) {

        $em = $this->getEntityManager();

        $reports = $em->createQuery('
            SELECT report, c FROM SpoutletBundle:ContentReport report
            LEFT JOIN report.comment c
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
            SELECT report, c FROM SpoutletBundle:ContentReport report
            LEFT JOIN report.groupDiscussion c
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

    public function deleteAllContentReportsForGroupDiscussionPost($content) {
        $em = $this->getEntityManager();

        $reports = $em->createQuery('
            SELECT report, c FROM SpoutletBundle:ContentReport report
            LEFT JOIN report.groupDiscussionPost c
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
