<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Model\ExportQueryManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExportQueryController extends Controller
{
    public function reportsAction(Request $request)
    {
        $form = $this->createForm('platformd_export_query_type');

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            $data   = $form->getData();
            $csv    = $this->getReportResults($data['reportTypes'], array('fromDate' => $data['fromDate'], 'thruDate' => $data['thruDate']));

            return $csv;
        }

        return $this->render('SpoutletBundle:ExportQuery:reports.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    private function getReportResults($reportType, $options)
    {
        return $this->getExportQueryManager()->getReport($reportType, $options);
    }

    private function getExportQueryManager()
    {
        return $this->get('platformd.model.export_query_manager');
    }
}
