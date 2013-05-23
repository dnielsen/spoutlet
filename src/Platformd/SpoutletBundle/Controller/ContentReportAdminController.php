<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\ContentReport;
use Platformd\SpoutletBundle\Form\Type\ReportedContentType;
use Platformd\CEVOBundle\Api\ApiException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;

class ContentReportAdminController extends Controller
{

    public function listAction($mode, $site=null)
    {
        if ($mode != "manage" && $mode != "archived" && $mode != "deletedContent") {
            throw new \Exception(sprintf("Unknown mode = '%s'.", $mode));
        }

        if ($this->isGranted('ROLE_JAPAN_ADMIN')) {
            $site = 2;
        }

        $this->addReportedContentsBreadcrumb();
        $em = $this->getDoctrine()->getEntityManager();

        $site = $site ? $em->getRepository('SpoutletBundle:Site')->find($site) : null;

        $repo = $em->getRepository('SpoutletBundle:ContentReport');

        $allowArchived = $mode == "archived";

        if ($mode == "archived") {
            $comments               = $repo->getContentReportTypeArchived("Comment", $site);
            $groupNews              = $repo->getContentReportTypeArchived("GroupNews", $site);
            $groupVideos            = $repo->getContentReportTypeArchived("GroupVideo", $site);
            $groupImages            = $repo->getContentReportTypeArchived("GroupImage", $site);
            $galleryMedia           = $repo->getContentReportTypeArchived("GalleryMedia", $site);
            $groups                 = $repo->getContentReportTypeArchived("Group", $site);
            $groupDiscussions       = $repo->getContentReportTypeArchived("GroupDiscussion", $site);
            $groupDiscussionPosts   = $repo->getContentReportTypeArchived("GroupDiscussionPost", $site);
            $groupEvents            = $repo->getContentReportTypeArchived("GroupEvent", $site);
            $contests               = $em->getRepository('SpoutletBundle:Contest')->findContestsByGroups($groups);
        } elseif ($mode == "deletedContent") {
            $comments               = $repo->getContentReportTypeDeletedContent("Comment", $site);
            $groupNews              = $repo->getContentReportTypeDeletedContent("GroupNews", $site);
            $groupVideos            = $repo->getContentReportTypeDeletedContent("GroupVideo", $site);
            $groupImages            = $repo->getContentReportTypeDeletedContent("GroupImage", $site);
            $galleryMedia           = $repo->getContentReportTypeDeletedContent("GalleryMedia", $site);
            $groups                 = $repo->getContentReportTypeDeletedContent("Group", $site);
            $groupDiscussions       = $repo->getContentReportTypeDeletedContent("GroupDiscussion", $site);
            $groupDiscussionPosts   = $repo->getContentReportTypeDeletedContent("GroupDiscussionPost", $site);
            $groupEvents            = $repo->getContentReportTypeDeletedContent("GroupEvent", $site);
            $contests               = $em->getRepository('SpoutletBundle:Contest')->findContestsByGroups($groups);
        } elseif ($mode == "manage") {
            $comments               = $repo->getContentReportType("Comment", $site);
            $groupNews              = $repo->getContentReportType("GroupNews", $site);
            $groupVideos            = $repo->getContentReportType("GroupVideo", $site);
            $groupImages            = $repo->getContentReportType("GroupImage", $site);
            $galleryMedia           = $repo->getContentReportType("GalleryMedia", $site);
            $groups                 = $repo->getContentReportType("Group", $site);
            $groupDiscussions       = $repo->getContentReportType("GroupDiscussion", $site);
            $groupDiscussionPosts   = $repo->getContentReportType("GroupDiscussionPost", $site);
            $groupEvents            = $repo->getContentReportType("GroupEvent", $site);
            $contests               = $em->getRepository('SpoutletBundle:Contest')->findContestsByGroups($groups);
        }

        $allReports = array_merge($comments, $groupNews, $groupVideos, $groupImages, $galleryMedia, $groups, $groupDiscussions, $groupDiscussionPosts, $groupEvents);

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
            'mode' => $mode,
            'contests' => $contests,
        ));
    }

    public function siteListAction($mode, $site)
    {

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
        $groupDiscussion        = $report->getGroupDiscussion();
        $groupDiscussionPost    = $report->getGroupDiscussionPost();
        $groupEvent = $report->getGroupEvent();

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
        } else if ($groupDiscussion) {
            $groupDiscussion->setDeleted(false);
            $groupDiscussion->setDeletedReason(null);
            $repo->deleteAllContentReportsForGroupDiscussion($groupDiscussion);
            $type = 'GroupDiscussion';
            $id = $groupDiscussion->getId();
            $reportedItem = $groupDiscussion;
        } else if ($groupDiscussionPost) {
            $groupDiscussionPost->setDeleted(false);
            $groupDiscussionPost->setDeletedReason(null);
            $repo->deleteAllContentReportsForGroupDiscussionPost($groupDiscussionPost);
            $type = 'GroupDiscussionPost';
            $id = $groupDiscussionPost->getId();
            $reportedItem = $groupDiscussionPost;
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
        } else if ($groupEvent) {
            $groupEvent->setDeleted(false);
            $groupEvent->setDeletedReason(null);
            $repo->deleteAllContentReportsForGroupEvent($groupEvent);
            $type = 'GroupEvent';
            $id = $groupEvent->getId();
        } else {
            $this->setFlash('error', 'Unknown content type.');
            return $this->redirect($this->generateUrl('admin_content_reports'));
        }

        $em->persist($report);
        $em->flush();

        $this->sendUserNotificationEmail($id, $type);

        $this->setFlash('success', 'Content has been reinstated and the complaint has been hidden.');
        return $this->redirect($this->generateUrl('admin_content_reports'));
    }

    public function removeContentAction($contentReportId)
    {
        $this->addReportedContentsBreadcrumb();
        $em = $this->getDoctrine()->getEntityManager();

        $repo = $em->getRepository('SpoutletBundle:ContentReport');

        $report = $repo->find($contentReportId);

        $groupVideo             = $report->getGroupVideo();
        $groupNews              = $report->getGroupNews();
        $groupImage             = $report->getGroupImage();
        $galleryMedia           = $report->getGalleryMedia();
        $group                  = $report->getGroup();
        $comment                = $report->getComment();
        $groupDiscussion        = $report->getGroupDiscussion();
        $groupDiscussionPost    = $report->getGroupDiscussionPost();
        $groupEvent             = $report->getGroupEvent();

        if ($groupVideo) {

            $groupVideo->setDeleted(true);
            $groupVideo->setDeletedReason('REPORTED_AND_REMOVED_BY_ADMIN');
            $em->persist($groupVideo);
            $repo->deleteAllContentReportsForGroupVideo($groupVideo);
            $id = $groupVideo->getId();
            $type = "GroupVideo";

        } else if ($groupNews) {

            $groupNews->setDeleted(true);
            $groupNews->setDeletedReason('REPORTED_AND_REMOVED_BY_ADMIN');
            $em->persist($groupNews);
            $repo->deleteAllContentReportsForGroupNews($groupNews);
            $id = $groupNews->getId();
            $type = "GroupNews";

        } else if ($groupImage) {

            $groupImage->setDeleted(true);
            $groupImage->setDeletedReason('REPORTED_AND_REMOVED_BY_ADMIN');
            $em->persist($groupImage);
            $repo->deleteAllContentReportsForGroupImage($groupImage);
            $id = $groupImage->getId();
            $type = "GroupImage";

        } else if ($galleryMedia) {

            $galleryMedia->setDeleted(true);
            $galleryMedia->setDeletedReason('REPORTED_AND_REMOVED_BY_ADMIN');
            $em->persist($galleryMedia);
            $repo->deleteAllContentReportsForGalleryMedia($galleryMedia);
            $id = $galleryMedia->getId();
            $type = "GalleryMedia";

            try {
                $response = $this->getCEVOApiManager()->GiveUserXp('nukephoto', $galleryMedia->getAuthor()->getCevoUserId());
            } catch (ApiException $e) {

            }

        } else if ($group) {

            $group->setDeleted(true);
            $group->setDeletedReason('REPORTED_AND_REMOVED_BY_ADMIN');
            $em->persist($group);
            $repo->deleteAllContentReportsForGroup($group);
            $id = $group->getId();
            $type = "Group";

            try {
                $response = $this->getCEVOApiManager()->GiveUserXp('groupnuke', $group->getOwner()->getCevoUserId());
            } catch (ApiException $e) {

            }

        } else if ($comment) {

            $comment->setDeleted(true);
            $comment->setDeletedReason('REPORTED_AND_REMOVED_BY_ADMIN');
            $em->persist($comment);
            $repo->deleteAllContentReportsForComment($comment);
            $id = $comment->getId();
            $type = "Comment";

        } else if ($groupDiscussion) {

            $groupDiscussion->setDeleted(true);
            $groupDiscussion->setDeletedReason('REPORTED_AND_REMOVED_BY_ADMIN');
            $em->persist($groupDiscussion);
            $repo->deleteAllContentReportsForGroupDiscussion($groupDiscussion);
            $id = $groupDiscussion->getId();
            $type = "GroupDiscussion";

        } else if ($groupDiscussionPost) {

            $groupDiscussionPost->setDeleted(true);
            $groupDiscussionPost->setDeletedReason('REPORTED_AND_REMOVED_BY_ADMIN');
            $em->persist($groupDiscussionPost);
            $repo->deleteAllContentReportsForGroupDiscussionPost($groupDiscussionPost);
            $id = $groupDiscussionPost->getId();
            $type = "GroupDiscussionPost";

        } else if ($groupEvent) {

            $groupEvent->setDeleted(true);
            $groupEvent->setDeletedReason('REPORTED_AND_REMOVED_BY_ADMIN');
            $em->persist($groupEvent);
            $repo->deleteAllContentReportsForGroupEvent($groupEvent);

        } else {

            $this->setFlash('error', 'Unknown content type.');
            return $this->redirect($this->generateUrl('admin_content_reports'));
        }

        $em->flush();

        $this->sendUserNotificationEmail($id, $type, true, $report);

        $this->setFlash('success', 'Content has been confirmed as removed.');
        return $this->redirect($this->generateUrl('admin_content_reports'));
    }

    public function reinstateContentAction($contentReportId)
    {
        $this->addReportedContentsBreadcrumb();
        $em = $this->getDoctrine()->getEntityManager();

        $repo = $em->getRepository('SpoutletBundle:ContentReport');

        $report = $repo->find($contentReportId);

        $groupVideo             = $report->getGroupVideo();
        $groupNews              = $report->getGroupNews();
        $groupImage             = $report->getGroupImage();
        $galleryMedia           = $report->getGalleryMedia();
        $group                  = $report->getGroup();
        $comment                = $report->getComment();
        $groupDiscussion        = $report->getGroupDiscussion();
        $groupDiscussionPost    = $report->getGroupDiscussionPost();
        $groupEvent             = $report->getGroupEvent();

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

        } else if ($groupDiscussion) {

            $groupDiscussion->setDeleted(false);
            $groupDiscussion->setDeletedReason(null);
            $em->persist($groupDiscussion);
            $type = 'GroupDiscussion';
            $id = $groupDiscussion->getId();
            $reportedItem = $groupDiscussion;

        } else if ($groupDiscussionPost) {

            $groupDiscussionPost->setDeleted(false);
            $groupDiscussionPost->setDeletedReason(null);
            $em->persist($groupDiscussionPost);
            $type = 'GroupDiscussionPost';
            $id = $groupDiscussionPost->getId();
            $reportedItem = $groupDiscussionPost;

        } else if ($groupEvent) {

            $groupEvent->setDeleted(false);
            $groupEvent->setDeletedReason(null);
            $em->persist($groupEvent);
            $type = 'GroupEvent';
            $id = $groupEvent->getId();
            $reportedItem = $groupEvent;

        } else {

            $this->setFlash('error', 'Unknown content type.');
            return $this->redirect($this->generateUrl('admin_content_reports'));
        }

        $em->flush();

        $this->sendUserNotificationEmail($id, $type);

        $this->setFlash('success', 'Content has been reinstated successfully.');

        return $this->redirect($this->generateUrl('admin_content_reports_with_mode', array('mode' => 'deletedContent')));
    }

    private function sendUserNotificationEmail($id, $type, $removed=false, $report=null)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $contentReportRepo = $this->getDoctrine()->getEntityManager()->getRepository('SpoutletBundle:ContentReport');
        $typeBundle = $contentReportRepo->getBundleFromType($type);
        $item = $em->getRepository($typeBundle.':'.$type)->find($id);

        switch ($type) {
            case 'GalleryMedia':
                $itemTypeKey = ContentReport::getTypeTranslationKey(ucfirst($item->getCategory()));
                $name = $item->getTitle();
                $owner = $item->getAuthor();
                $url = $this->generateUrl($item->getLinkableRouteName(), $item->getLinkableRouteParameters(), true);
                break;

            case 'Comment':
                $itemTypeKey = ContentReport::getTypeTranslationKey($type);
                $name = $item->getBody();
                $owner = $item->getAuthor();
                $url = 'http://'.$this->getCurrentSite()->getFullDomain().$item->getThread()->getPermalink();
                break;

            case 'Group':
                $itemTypeKey = ContentReport::getTypeTranslationKey($type);
                $name = $item->getName();
                $owner = $item->getOwner();
                $url = $this->generateUrl($item->getLinkableRouteName(), $item->getLinkableRouteParameters(), true);
                break;

            case 'GroupDiscussionPost':
                $itemTypeKey = ContentReport::getTypeTranslationKey($type);
                $name = $item->getContent();
                $owner = $item->getAuthor();
                $url = $this->generateUrl($item->getGroupDiscussion()->getLinkableRouteName(), $item->getGroupDiscussion()->getLinkableRouteParameters(), true);
                break;

            case 'GroupEvent':
                $itemTypeKey = ContentReport::getTypeTranslationKey($type);
                $name = $item->getName();
                $owner = $item->getUser();
                $url = $this->generateUrl($item->getLinkableRouteName(), $item->getLinkableRouteParameters(), true);
                break;

            case 'GroupEvent':
                $itemTypeKey = ContentReport::getTypeTranslationKey($type);
                $name = $item->getName();
                $owner = $item->getUser();
                break;

            default:
                $itemTypeKey = ContentReport::getTypeTranslationKey($type);
                $name = $item->getTitle();
                $owner = $item->getAuthor();
                $url = $this->generateUrl($item->getLinkableRouteName(), $item->getLinkableRouteParameters(), true);
                break;
        }

        $emailTo            = $owner->getEmail();
        $emailLocale        = $owner->getLocale() ? : 'en';
        $itemType           = $this->trans($itemTypeKey, array(), 'messages', $emailLocale);

        if (!$removed) {
            $subject    = $this->trans('content_reporting.restored_email_title', array(), 'messages', $emailLocale);
            $message    = nl2br(sprintf($this->trans('content_reporting.restored_email', array(), 'messages', $emailLocale), $itemType, $name));
            $this->getEmailManager()->sendHtmlEmail($emailTo, $subject, $message, "Reported Item Restored User Notification", $this->getCurrentSite()->getDefaultLocale());
        } else {
            $reason     = $report ? $this->trans('content_reporting.'.$report->getReason(), array(), 'messages', $emailLocale) : $this->trans('content_reporting.report_type_unknown', array(), 'messages', $emailLocale);
            $subject    = $this->trans('content_reporting.removed_email_title', array(), 'messages', $emailLocale);
            $message    = nl2br(sprintf($this->trans('content_reporting.removed_email', array(), 'messages', $emailLocale), $itemType, $name, $reason, $url, $url));
            $this->getEmailManager()->sendHtmlEmail($emailTo, $subject, $message, "Reported Item Removed User Notification", $this->getCurrentSite()->getDefaultLocale());
        }
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
