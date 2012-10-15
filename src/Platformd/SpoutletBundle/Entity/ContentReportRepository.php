<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;

class ContentReportRepository extends EntityRepository
{

    public function getContentReportTypeForAllSites($type)
    {

        if ($type != "GroupImage" && $type != "GroupVideo" && $type != "GroupNews") {
            throw new \Exception(sprintf("Unknown content report type = '%s'.", $type));
        }

        return $this->getEntityManager()->createQuery(sprintf('
            SELECT item, report FROM SpoutletBundle:%s item
            LEFT JOIN item.contentReports report
            WHERE item.deleted = false
            AND report.deleted = false
            GROUP BY item
            ORDER BY report.reportedAt
            ',
            $type))
            ->execute();
    }

    public function getContentReportTypeForAllSitesArchived($type) {

        if ($type != "GroupImage" && $type != "GroupVideo" && $type != "GroupNews") {
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

        if ($type != "GroupImage" && $type != "GroupVideo" && $type != "GroupNews") {
            throw new \Exception(sprintf("Unknown content report type = '%s'.", $type));
        }

        return $this->getEntityManager()->createQuery(sprintf('
            SELECT item, COUNT(DISTINCT report.id) reportCount FROM SpoutletBundle:%s item
            LEFT JOIN item.contentReports report
            WHERE item.deleted = true
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
            SELECT report, i, v, n FROM SpoutletBundle:ContentReport report
            LEFT JOIN report.groupImage i
            LEFT JOIN report.groupVideo v
            LEFT JOIN report.groupNews n
            WHERE report.deleted = false
            ORDER BY report.reportedAt
            ')
            ->execute();
    }

    public function getContentReportForSite($site)
    {

        return $this->getEntityManager()->createQuery('
            SELECT report, i, v, n FROM SpoutletBundle:ContentReport report
            LEFT JOIN report.groupImage i
            LEFT JOIN report.groupVideo v
            LEFT JOIN report.groupNews n
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
}
