<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Form\Type\ExportQueryType;
use Symfony\Component\HttpFoundation\Request;

class ExportQueryController extends Controller
{
    public function reportsAction(Request $request)
    {
        $form = $this->createForm(ExportQueryType::class);

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);

            $data   = $form->getData();
            $csv    = $this->getReportResults($data['reportTypes'], array(
                'fromDate' => $data['fromDate'],
                'thruDate' => $data['thruDate'],
                'sites'    => $data['sites']
            ));

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
