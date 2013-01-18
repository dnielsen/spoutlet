<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\ContentReport;
use Platformd\SpoutletBundle\Form\Type\ReportedContentType;
use Platformd\CEVOBundle\Api\ApiException;
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
            $comments    = $repo->getContentReportTypeForAllSitesArchived("Comment");
            $groupNews   = $repo->getContentReportTypeForAllSitesArchived("GroupNews");
            $groupVideos = $repo->getContentReportTypeForAllSitesArchived("GroupVideo");
            $groupImages = $repo->getContentReportTypeForAllSitesArchived("GroupImage");
            $galleryMedia = $repo->getContentReportTypeForAllSitesArchived("GalleryMedia");
            $groups      = $repo->getContentReportTypeForAllSitesArchived("Group");
        } elseif ($mode == "deletedContent") {
            $comments    = $repo->getContentReportTypeForAllSitesDeletedContent("Comment");
            $groupNews   = $repo->getContentReportTypeForAllSitesDeletedContent("GroupNews");
            $groupVideos = $repo->getContentReportTypeForAllSitesDeletedContent("GroupVideo");
            $groupImages = $repo->getContentReportTypeForAllSitesDeletedContent("GroupImage");
            $galleryMedia = $repo->getContentReportTypeForAllSitesDeletedContent("GalleryMedia");
            $groups      = $repo->getContentReportTypeForAllSitesDeletedContent("Group");
        } elseif ($mode == "manage") {
            $comments    = $repo->getContentReportTypeForAllSites("Comment");
            $groupNews   = $repo->getContentReportTypeForAllSites("GroupNews");
            $groupVideos = $repo->getContentReportTypeForAllSites("GroupVideo");
            $groupImages = $repo->getContentReportTypeForAllSites("GroupImage");

            $galleryMedia = $repo->getContentReportTypeForAllSites("GalleryMedia");
            $groups      = $repo->getContentReportTypeForAllSites("Group");
        }

        $allReports = array_merge($comments, $groupNews, $groupVideos, $groupImages, $galleryMedia, $groups);

        usort($allReports, function($a, $b) {

            $aReports       = $a[0]->getContentReports();
            $bReports       = $b[0]->getContentReports();

            $aReportCount   = $aReports->count();
            $bReportCount   = $bReports->count();

            if ($aReportCount == $bReportCount) {
                $aMostRecent  = $aReports[0]->getReportedAt();
                $bMostRecent  = $bReports[0]->getReportedAt();

                if ($aMostRecent == $bMostRecent) {
                    return 0;
                }

                return $aMostRecent > $bMostRecent ? -1 : 1;
            }

            return $aReportCount > $bReportCount ? -1 : 1;
        });

        return $this->render('SpoutletBundle:ContentReportAdmin:list.html.twig', array(
            'allReports' => $allReports,
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
        $groupNews  = $report->getGroupNews();
        $groupImage = $report->getGroupImage();
        $group      = $report->getGroup();
        $comment    = $report->getComment();
        $galleryMedia = $report->getGalleryMedia();

        if ($groupVideo) {
            $groupVideo->setDeleted(false);
            $groupVideo->setDeletedReason(null);
            $repo->deleteAllContentReportsForGroupVideo($groupVideo);
            $type = 'GroupVideo';
            $id = $groupVideo->getId();
        } else if ($groupNews) {
            $groupNews->setDeleted(false);
            $groupNews->setDeletedReason(null);
            $repo->deleteAllContentReportsForGroupNews($groupNews);
            $type = 'GroupNews';
            $id = $groupNews->getId();
        } else if ($groupImage) {
            $groupImage->setDeleted(false);
            $groupImage->setDeletedReason(null);
            $repo->deleteAllContentReportsForGroupImage($groupImage);
            $type = 'GroupImage';
            $id = $groupImage->getId();
        } else if ($galleryMedia) {
            $galleryMedia->setDeleted(false);
            $galleryMedia->setDeletedReason(null);
            $repo->deleteAllContentReportsForGalleryMedia($galleryMedia);
            $type = 'GalleryMedia';
            $id = $galleryMedia->getId();
        } else if ($group) {
            $group->setDeleted(false);
            $group->setDeletedReason(null);
            $repo->deleteAllContentReportsForGroup($group);
            $type = 'Group';
            $id = $group->getId();
        } else if ($comment) {
            $comment->setDeleted(false);
            $comment->setDeletedReason(null);
            $repo->deleteAllContentReportsForComment($comment);
            $type = 'Comment';
            $id = $comment->getId();
        } else {
            $this->setFlash('error', 'Unknown content type.');
            return $this->redirect($this->generateUrl('admin_content_reports'));
        }

        $em->persist($report);
        $em->flush();

        $this->sendUserRestoredNotificationEmail($id, $type);

        $this->setFlash('success', 'Content has been reinstated and the complaint has been hidden.');
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
        $galleryMedia = $report->getGalleryMedia();
        $group          = $report->getGroup();
        $comment    = $report->getComment();

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

        } else if ($galleryMedia) {

            $galleryMedia->setDeleted(true);
            $galleryMedia->setDeletedReason('REPORTED_AND_REMOVED_BY_ADMIN');
            $em->persist($galleryMedia);
            $repo->deleteAllContentReportsForGalleryMedia($galleryMedia);

            try {
                $response = $this->getCEVOApiManager()->GiveUserXp('nukephoto', $galleryMedia->getAuthor()->getCevoUserId());
            } catch (ApiException $e) {

            }

        } else if ($group) {

            $group->setDeleted(true);
            $group->setDeletedReason('REPORTED_AND_REMOVED_BY_ADMIN');
            $em->persist($group);
            $repo->deleteAllContentReportsForGroup($group);

            try {
                $response = $this->getCEVOApiManager()->GiveUserXp('groupnuke', $group->getOwner()->getCevoUserId());
            } catch (ApiException $e) {

            }

        } else if ($comment) {

            $comment->setDeleted(true);
            $comment->setDeletedReason('REPORTED_AND_REMOVED_BY_ADMIN');
            $em->persist($comment);
            $repo->deleteAllContentReportsForComment($comment);

        } else {

            $this->setFlash('error', 'Unknown content type.');
            return $this->redirect($this->generateUrl('admin_content_reports'));
        }

        $em->flush();

        $this->setFlash('success', 'Content has been confirmed as removed.');
        return $this->redirect($this->generateUrl('admin_content_reports'));
    }

    public function reinstateContentAction($contentReportId)
    {
        $this->addReportedContentsBreadcrumb();
        $em = $this->getDoctrine()->getEntityManager();

        $repo = $em->getRepository('SpoutletBundle:ContentReport');

        $report = $repo->find($contentReportId);

        $groupVideo = $report->getGroupVideo();
        $groupNews = $report->getGroupNews();
        $groupImage = $report->getGroupImage();
        $galleryMedia = $report->getGalleryMedia();
        $group          = $report->getGroup();
        $comment    = $report->getComment();

        if ($groupVideo) {

            $groupVideo->setDeleted(false);
            $groupVideo->setDeletedReason(null);
            $em->persist($groupVideo);
            $type = 'GroupVideo';
            $id = $groupVideo->getId();

        } else if ($groupNews) {

            $groupNews->setDeleted(false);
            $groupNews->setDeletedReason(null);
            $em->persist($groupNews);
            $type = 'GroupNews';
            $id = $groupNews->getId();

        } else if ($groupImage) {

            $groupImage->setDeleted(false);
            $groupImage->setDeletedReason(null);
            $em->persist($groupImage);
            $type = 'GroupImage';
            $id = $groupImage->getId();

        } else if ($galleryMedia) {

            $galleryMedia->setDeleted(false);
            $galleryMedia->setDeletedReason(null);
            $em->persist($galleryMedia);
            $type = 'GalleryMedia';
            $id = $galleryMedia->getId();

        } else if ($group) {

            $group->setDeleted(false);
            $group->setDeletedReason(null);
            $em->persist($group);
            $type = 'Group';
            $id = $group->getId();

        } else if ($comment) {

            $comment->setDeleted(false);
            $comment->setDeletedReason(null);
            $em->persist($comment);
            $type = 'Comment';
            $id = $comment->getId();

        } else {

            $this->setFlash('error', 'Unknown content type.');
            return $this->redirect($this->generateUrl('admin_content_reports'));
        }

        $em->flush();

        $this->sendUserRestoredNotificationEmail($id, $type);

        $this->setFlash('success', 'Content has been reinstated successfully.');

        return $this->redirect($this->generateUrl('admin_content_reports_with_mode', array('mode' => 'deletedContent')));
    }

    private function sendUserRestoredNotificationEmail($id, $type)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $item = $em->getRepository('SpoutletBundle:'.$type)->find($id);

        switch ($type) {
            case 'GalleryMedia':
                $itemTypeKey = ContentReport::getTypeTranslationKey(ucfirst($item->getCategory()));
                $name = $item->getTitle();
                $owner = $item->getAuthor();
                break;

            case 'Comment':
                $itemTypeKey = ContentReport::getTypeTranslationKey($type);
                $name = $item->getBody();
                $owner = $item->getAuthor();
                break;

            case 'Group':
                $itemTypeKey = ContentReport::getTypeTranslationKey($type);
                $name = $item->getName();
                $owner = $item->getOwner();
                break;

            case 'GroupDiscussionPost':
                $itemTypeKey = ContentReport::getTypeTranslationKey($type);
                $name = $item->getContent();
                $owner = $item->getOwner();
                break;

            default:
                $itemTypeKey = ContentReport::getTypeTranslationKey($type);
                $name = $item->getTitle();
                $owner = $item->getAuthor();
                break;
        }

        $fromEmail          = $this->container->getParameter('sender_email_address');
        $fromName           = $this->container->getParameter('sender_email_name');
        $emailTo            = $owner->getEmail();
        $emailLocale        = $owner->getLocale() ? : 'en';
        $itemType           = $this->trans($itemTypeKey, array(), 'messages', $emailLocale);
        $subject            = $this->trans('content_reporting.restored_email_title', array(), 'messages', $emailLocale);
        $message            = sprintf($this->trans('content_reporting.restored_email', array(), 'messages', $emailLocale), $itemType, $name);

        $this->getEmailManager()->sendEmail($emailTo, $subject, $message, "Reported Item Restored User Notification", $this->getCurrentSite()->getDefaultLocale(), $fromName, $fromEmail);
    }

    private function addReportedContentsBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Content Reports', array(
            'route' => 'admin_content_reports'
        ));

        return $this->getBreadcrumbs();
    }

    private function getEmailManager()
    {
        return $this->get('platformd.model.email_manager');
    }

    private function getCEVOApiManager()
    {
        return $this->get('pd.cevo.api.api_manager');
    }
}
