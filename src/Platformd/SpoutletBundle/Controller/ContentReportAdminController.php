<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\ContentReport;
use Platformd\SpoutletBundle\Form\Type\ReportedContentType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;

class ContentReportAdminController extends Controller
{
    public function indexAction()
    {
        $this->addReportedContentsBreadcrumb();
        $em = $this->getDoctrine()->getEntityManager();

        return $this->render('SpoutletBundle:ContentReportAdmin:index.html.twig', array(
            'sites' => MultitenancyManager::getSiteChoices()
        ));
    }

    public function listAction($site)
    {
        $this->addReportedContentsBreadcrumb();
        $em = $this->getDoctrine()->getEntityManager();
        $site = $this->getSiteFromDefaultLocale($site);

        if (!$site) {
            $this->setFlash('error', 'Could not find site with that locale.');
            return $this->redirect($this->generateUrl('admin_content_reports'));
        }

        $repo = $em->getRepository('SpoutletBundle:ContentReport');

        $reports = $repo->getContentReportForSite($site);

        return $this->render('SpoutletBundle:ContentReportAdmin:list.html.twig', array(
            'site' => $site->getDefaultLocale(),
            'reports' => $reports
        ));
    }

    public function hideComplaintAction($site, $contentReportId)
    {
        $this->addReportedContentsBreadcrumb();
        $em = $this->getDoctrine()->getEntityManager();
        $site = $this->getSiteFromDefaultLocale($site);

        if (!$site) {
            $this->setFlash('error', 'Could not find site with that locale.');
            return $this->redirect($this->generateUrl('admin_content_reports'));
        }

        $repo = $em->getRepository('SpoutletBundle:ContentReport');

        $report = $repo->find($contentReportId);

        $report->setDeleted(true);

        $em->persist($report);
        $em->flush();

        $this->setFlash('success', 'Complaint has been hidden.');
        return $this->redirect($this->generateUrl('admin_content_reports_site', array('site' => $site->getDefaultLocale())));
    }

    public function removeContentAction($site, $contentReportId)
    {
        $this->addReportedContentsBreadcrumb();
        $em = $this->getDoctrine()->getEntityManager();
        $site = $this->getSiteFromDefaultLocale($site);

        if (!$site) {
            $this->setFlash('error', 'Could not find site with that locale.');
            return $this->redirect($this->generateUrl('admin_content_reports'));
        }

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
            return $this->redirect($this->generateUrl('admin_content_reports_site', array('site' => $site->getDefaultLocale())));
        }

        $em->flush();

        $this->setFlash('success', 'Content has been removed successfully.');
        return $this->redirect($this->generateUrl('admin_content_reports_site', array('site' => $site->getDefaultLocale())));
    }

    private function addReportedContentsBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Content Reports', array(
            'route' => 'admin_content_reports'
        ));

        return $this->getBreadcrumbs();
    }
}
