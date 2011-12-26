<?php

namespace Platformd\UserBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/admin/metrics/users")
 */
class MetricController extends Controller
{
    /**
     * @Route("/country", name="user_metric_by_country")
     * @Template()
     */
    public function membershipByCountryAction()
    {
        $metricManager = $this->container->get('platformd.metric_manager');

        $this->getBreadcrumbs()->addChild('Metrics');
        $this->getBreadcrumbs()->addChild('Members');

        $report = $metricManager->createMembershipByCountryReport();

        return array(
            'sitesData' => $report,
        );
    }
}