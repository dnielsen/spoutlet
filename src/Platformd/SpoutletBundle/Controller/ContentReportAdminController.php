<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\ContentReport;
use Platformd\SpoutletBundle\Form\Type\ReportedContentType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;

class ContentReportAdminController extends Controller
{

    public function listAction($mode)
    {

        if ($mode != "manage" && $mode != "archived" && $mode != "deletedContent") {
            throw new \Exception(sprintf("Unknown mode = '%s'.", $mode));
        }

        $this->addReportedContentsBreadcrumb();
        $em = $this->getDoctrine()->getEntityManager();

        $repo = $em->getRepository('SpoutletBundle:ContentReport');

        $allowArchived = $mode == "archived";

        if ($mode == "archived") {
            $groupNews   = $repo->getContentReportTypeForAllSitesArchived("GroupNews");
            $groupVideos = $repo->getContentReportTypeForAllSitesArchived("GroupVideo");
            $groupImages = $repo->getContentReportTypeForAllSitesArchived("GroupImage");
        } elseif ($mode == "deletedContent") {
            $groupNews   = $repo->getContentReportTypeForAllSitesDeletedContent("GroupNews");
            $groupVideos = $repo->getContentReportTypeForAllSitesDeletedContent("GroupVideo");
            $groupImages = $repo->getContentReportTypeForAllSitesDeletedContent("GroupImage");
        } elseif ($mode == "manage") {
            $groupNews   = $repo->getContentReportTypeForAllSites("GroupNews");
            $groupVideos = $repo->getContentReportTypeForAllSites("GroupVideo");
            $groupImages = $repo->getContentReportTypeForAllSites("GroupImage");
        }

        return $this->render('SpoutletBundle:ContentReportAdmin:list.html.twig', array(
            'groupNews' => $groupNews,
            'groupVideos' => $groupVideos,
            'groupImages' => $groupImages,
            'mode' => $mode
        ));
    }

    public function hideComplaintAction($contentReportId)
    {
        $this->addReportedContentsBreadcrumb();
        $em = $this->getDoctrine()->getEntityManager();

        $repo = $em->getRepository('SpoutletBundle:ContentReport');

        $report = $repo->find($contentReportId);

        $groupVideo = $report->getGroupVideo();
        $groupNews = $report->getGroupNews();
        $groupImage = $report->getGroupImage();

        if ($groupVideo) {
            $repo->deleteAllContentReportsForGroupVideo($groupVideo);
        } else if ($groupNews) {
            $repo->deleteAllContentReportsForGroupNews($groupNews);
        } else if ($groupImage) {
            $repo->deleteAllContentReportsForGroupImage($groupImage);
        } else {
            $this->setFlash('error', 'Unknown content type.');
            return $this->redirect($this->generateUrl('admin_content_reports'));
        }

        $em->persist($report);
        $em->flush();

        $this->setFlash('success', 'Complaint has been hidden.');
        return $this->redirect($this->generateUrl('admin_content_reports'));
    }

    public function removeContentAction($contentReportId)
    {
        $this->addReportedContentsBreadcrumb();
        $em = $this->getDoctrine()->getEntityManager();

        $repo = $em->getRepository('SpoutletBundle:ContentReport');

        $report = $repo->find($contentReportId);

        $groupVideo = $report->getGroupVideo();
        $groupNews = $report->getGroupNews();
        $groupImage = $report->getGroupImage();

        if ($groupVideo) {

            $groupVideo->setDeleted(true);
            $groupVideo->setDeletedReason('REPORTED_AND_REMOVED_BY_ADMIN');
            $em->persist($groupVideo);
            $repo->deleteAllContentReportsForGroupVideo($groupVideo);

        } else if ($groupNews) {

            $groupNews->setDeleted(true);
            $groupNews->setDeletedReason('REPORTED_AND_REMOVED_BY_ADMIN');
            $em->persist($groupNews);
            $repo->deleteAllContentReportsForGroupNews($groupNews);

        } else if ($groupImage) {

            $groupImage->setDeleted(true);
            $groupImage->setDeletedReason('REPORTED_AND_REMOVED_BY_ADMIN');
            $em->persist($groupImage);
            $repo->deleteAllContentReportsForGroupImage($groupImage);

        } else {

            $this->setFlash('error', 'Unknown content type.');
            return $this->redirect($this->generateUrl('admin_content_reports'));
        }

        $em->flush();

        $this->setFlash('success', 'Content has been removed successfully.');
        return $this->redirect($this->generateUrl('admin_content_reports'));
    }

    private function addReportedContentsBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Content Reports', array(
            'route' => 'admin_content_reports'
        ));

        return $this->getBreadcrumbs();
    }
}
