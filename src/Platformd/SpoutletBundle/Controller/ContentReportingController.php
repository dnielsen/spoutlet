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

        if (!in_array($reason, $report->getValidReasons())) {
            $response->setContent(json_encode(array("success" => false, "messageForUser" => "Valid reason not given.")));
            return $response;
        }

        if (!($user instanceof User)) {
            $response->setContent(json_encode(array("success" => false, "messageForUser" => "You must be logged in to report content.")));
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

        $content->getContentReports()->add($report);

        $em = $this->getDoctrine()->getEntityManager();

        $em->persist($content);
        $em->flush();

        $response->setContent(json_encode(array("success" => true)));
        return $response;
    }
}
