<?php

namespace Platformd\UserBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use DateTime;

/**
 * @Route("/admin/metrics/users")
 */
class MetricController extends Controller
{
    /**
     * @Route("/country", name="user_metric_by_country")
     * @Template()
     */
    public function membershipByCountryAction(Request $request)
    {
        $metricManager = $this->container->get('platformd.metric_manager');

        $this->getBreadcrumbs()->addChild('Metrics');
        $this->getBreadcrumbs()->addChild('Members');

        $em     = $this->getDoctrine()->getEntityManager();
        $site   = $this->isGranted('ROLE_JAPAN_ADMIN') ? $em->getRepository('SpoutletBundle:Site')->find(2) : null;

        // create a select field for range
        $select = $this->get('form.factory')
            ->createNamedBuilder('choice', 'results_range', 7, array(
             'choices' => array(
                        '7'  => 'Last 7 days',
                        '30' => 'Last 30 days',
                        ''   => 'All time',
        ),
        ))->getForm();

        // bind only if we have that query parameter
        if (null !== $request->query->get($select->getName())) {
            $select->bindRequest($request);
        }

        // used for MySQL query
        $since = ($range = $select->getData()) ? new DateTime(sprintf('%s days ago', $range)) : null;
        // For display, so the admin can what qury was performed
        $dateRange = ($range = $select->getData()) ? sprintf('Last %s days', $range) : 'All time';

        $report = $metricManager->createMembershipByCountryReport($since, $site);

        return array(
            'sitesData' => $report,
            'select'    => $select->createView(),
            'range'     => $dateRange
        );
    }
}
