<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Model\ReportableContentInterface;
use Platformd\SpoutletBundle\Entity\Group;
use Platformd\SpoutletBundle\Entity\GroupNews;
use Platformd\SpoutletBundle\Entity\GroupVideo;
use Platformd\SpoutletBundle\Entity\ContentReport;
use Platformd\SpoutletBundle\Form\Type\GroupType;
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
            $response->setContent(json_encode(array("success" => false, "messageForUser" => "You must be logged in to report content.")));
            return $response;
        }

        if ($lastReport && $lastReport > new \DateTime('-1 hour')) {
            $response->setContent(json_encode(array("success" => false, "messageForUser" => "You may only report one item per hour.")));
            return $response;
        }

        $fullClassName      = 'Platformd\\SpoutletBundle\\Entity\\'.$type;
        $fullInterfaceName  = 'Platformd\\SpoutletBundle\\Model\\ReportableContentInterface';

        if (!class_exists($fullClassName) || !in_array($fullInterfaceName, class_implements($fullClassName))) {
            return new Response(json_encode(array("success" => false, "messageForUser" => "Valid content type not given.")));
        }

        $content = $this->getDoctrine()->getEntityManager()->getRepository(sprintf('SpoutletBundle:%s', $type))->find($id);

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

        $reportedItem = $em->getRepository('SpoutletBundle:'.$type)->find($id);
        $reportedItem->setDeleted(true);
        $reportedItem->setDeletedReason('REPORTED_PENDING_INVESTIGATION');

        $em->persist($reportedItem);

        $em->flush();

        //$this->sendUserNotificationEmail($id, $type, $reason);
        //$this->sendAdminNotificationEmail($id, $type, $reason);

        $response->setContent(json_encode(array("success" => true, "messageForUser" => "This content will be reviewed by our staff. If it violates our Terms of Service, it will be removed. If you have additional information for your report, please email us at contact@alienwarearena.com with the additional details.")));
        return $response;
    }

    private function sendUserNotificationEmail($id, $type, $reason)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $item = $em->getRepository('SpoutletBundle:'.$type)->find($id);

        $emailTo = $item->getAuthor()->getEmail();
        $url     = "http://www.alienwarearena.com/inappropriate";

        $fromEmail          = $this->container->getParameter('sender_email_address');
        $fromName           = $this->container->getParameter('sender_email_name');

        $subject            = "Post flagged as inappropriate";
        $message            = sprintf("Something you posted on Alienware Arena has been flagged by another user as inappropriate and has been temporarily removed from the site.

            You can view the item at %s.

            The item will be reviewed shortly by an admin to decide if it should be removed permanently.

            Alienware Arena Team
", $url);

        $this->getEmailManager()->sendEmail($emailTo, $subject, $message, "Content Reported User Notification", $this->getCurrentSite()->getDefaultLocale(), $fromName, $fromEmail);
    }

    private function sendAdminNotificationEmail($id, $type, $reason)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $item = $em->getRepository('SpoutletBundle:'.$type)->find($id);

        $emailTo = "contentreporting@alienwarearena.com";
        $url     = "http://www.alienwarearena.com/inappropriate";

        $fromEmail          = $this->container->getParameter('sender_email_address');
        $fromName           = $this->container->getParameter('sender_email_name');

        $subject            = "Post flagged as inappropriate";
        $message            = sprintf("An item posted at %s has been flagged as inappropriate and requires review.

            Alienware Arena Team
", $url);

        $this->getEmailManager()->sendEmail($emailTo, $subject, $message, "Content Reported Admin Notification", $this->getCurrentSite()->getDefaultLocale(), $fromName, $fromEmail);
    }

    private function getEmailManager()
    {
        return $this->get('platformd.model.email_manager');
    }
}
