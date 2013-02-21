<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\ContentReportEvents;
use Platformd\SpoutletBundle\Event\ContentReportEvent;
use Platformd\SpoutletBundle\Model\ReportableContentInterface;
use Platformd\GroupBundle\Entity\Group;
use Platformd\GroupBundle\Entity\GroupNews;
use Platformd\GroupBundle\Entity\GroupVideo;
use Platformd\SpoutletBundle\Entity\ContentReport;
use Platformd\GroupBundle\Form\Type\GroupType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Response;
use Platformd\UserBundle\Entity\User;

class ContentReportingController extends Controller
{
    private function getCurrentUser()
    {
        return $this->get('security.context')->getToken()->getUser();
    }

    public function reportContentAction(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-type', 'text/json; charset=utf-8');

        $params   = array();
        $content  = $request->getContent();

        if (empty($content)) {
            $response->setContent(json_encode(array("success" => false, "messageForUser" => "Some required information was not passed.")));
            return $response;
        }

        $params = json_decode($content, true);

        if (!isset($params['ContentType']) || !isset($params['ContentId']) || !isset($params['Reason'])) {
            $response->setContent(json_encode(array("success" => false, "messageForUser" => "Some required information was not passed.")));
            return $response;
        }

        $id     = (int) $params['ContentId'];
        $type   = $params['ContentType'];
        $reason = $params['Reason'];
        $report = new ContentReport();
        $user   = $this->getCurrentUser();
        $site   = $this->getCurrentSite();

        $contentReportRepo = $this->getDoctrine()->getEntityManager()->getRepository('SpoutletBundle:ContentReport');
        $lastReport = $contentReportRepo->getLastReportDateForUser($user);

        if (!in_array($reason, $report->getValidReasons())) {
            $response->setContent(json_encode(array("success" => false, "messageForUser" => "Valid reason not given.")));
            return $response;
        }

        if (!($user instanceof User)) {
            $response->setContent(json_encode(array("success" => false, "messageForUser" => $this->trans('content_reporting.must_be_logged_in'))));
            return $response;
        }

        if ($lastReport && $lastReport > new \DateTime('-1 hour')) {
            $response->setContent(json_encode(array("success" => false, "messageForUser" => $this->trans('content_reporting.once_per_hour'))));
            return $response;
        }

        $typeBundle         = $contentReportRepo->getBundleFromType($type);

        $fullClassName      = 'Platformd\\'.$typeBundle.'\\Entity\\'.$type;
        $fullInterfaceName  = 'Platformd\\SpoutletBundle\\Model\\ReportableContentInterface';

        if (!class_exists($fullClassName) || !in_array($fullInterfaceName, class_implements($fullClassName))) {
            return new Response(json_encode(array("success" => false, "messageForUser" => "Valid content type not given.")));
        }

        $content = $this->getDoctrine()->getEntityManager()->getRepository(sprintf('%s:%s', $typeBundle, $type))->find($id);

        if (!$content) {
            $response->setContent(json_encode(array("success" => false, "messageForUser" => "Could not find the content that you are reporting (perhaps it has been removed already).")));
            return $response;
        }

        foreach ($content->getContentReports() as $r) {
            if ($r->getReporter()->getId() == $user->getId()) {
                $response->setContent(json_encode(array("success" => false, "messageForUser" => "You have already reported this content.")));
                return $response;
            }
        }

        $report->setReason($reason);
        $report->setReporter($user);
        $report->setSite($site);

        $setType = 'set'.ucfirst($type);

        $report->$setType($content);

        $em = $this->getDoctrine()->getEntityManager();

        $em->persist($report);

        $reportedItem = $em->getRepository($typeBundle.':'.$type)->find($id);

        $sendEmail = true;

        switch ($type) {
            case 'GalleryMedia':
                if($reportedItem->getContestEntry()) {
                    $sendEmail = false;
                    $reportedItem->setDeleted(false);
                } else {
                    $reportedItem->setDeleted(true);
                    $reportedItem->setDeletedReason('REPORTED_PENDING_INVESTIGATION');
                }
                break;

            case 'Group':
                $contest = $this->getDoctrine()->getEntityManager()->getRepository('SpoutletBundle:Contest')->findContestByGroup($reportedItem);

                if($contest) {
                    $sendEmail = false;
                    $reportedItem->setDeleted(false);
                } else {
                    $reportedItem->setDeleted(true);
                    $reportedItem->setDeletedReason('REPORTED_PENDING_INVESTIGATION');
                }
                break;

            default:
                $reportedItem->setDeleted(true);
                $reportedItem->setDeletedReason('REPORTED_PENDING_INVESTIGATION');
                break;
        }

        $em->persist($reportedItem);

        $em->flush();

        /* Disabled at present as causing issues with reporting.
        // We dispatch an event for further stuff like maintaining counts
        $eventName = ContentReportEvents::REPORT;
        $event = new ContentReportEvent($reportedItem, $this->getUser());
        $this->get('event_dispatcher')->dispatch($eventName, $event);
        */

        if($sendEmail) {
            $this->sendUserReportedNotificationEmail($id, $type, $reason);
        }

        $response->setContent(json_encode(array("success" => true, "messageForUser" => "This content will be reviewed by our staff. If it violates our Terms of Service, it will be removed. If you have additional information for your report, please email us at contact@alienwarearena.com with the additional details.")));
        return $response;
    }

    private function sendUserReportedNotificationEmail($id, $type, $reason)
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
        $reason             = $this->trans('content_reporting.'.$reason, array(), 'messages', $emailLocale);
        $subject            = $this->trans('content_reporting.reported_email_title', array(), 'messages', $emailLocale);
        $message            = sprintf($this->trans('content_reporting.reported_email', array(), 'messages', $emailLocale), $itemType, $name, $reason);


        $this->getEmailManager()->sendEmail($emailTo, $subject, $message, "Content Reported User Notification", $this->getCurrentSite()->getDefaultLocale(), $fromName, $fromEmail);
    }

    private function getEmailManager()
    {
        return $this->get('platformd.model.email_manager');
    }
}
