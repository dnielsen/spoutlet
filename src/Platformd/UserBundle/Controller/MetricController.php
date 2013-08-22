<?php

namespace Platformd\UserBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use DateTime;

use Platformd\SpoutletBundle\Entity\Country;
use Platformd\SpoutletBundle\Entity\Region;

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

        $localAuth = $this->container->getParameter('local_auth');

        if ($this->isGranted('ROLE_JAPAN_ADMIN')) {

            $em     = $this->getDoctrine()->getEntityManager();
            $site   = $em->getRepository('SpoutletBundle:Site')->find(2);

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

            return $this->render('UserBundle:Metric:membershipByCountry.html.twig', array(
                'sitesData' => $report,
                'select'    => $select->createView(),
                'range'     => $dateRange
            ));

        } else {

            $form = $this->createFormBuilder()
                ->add('country', 'entity', array(
                    'class'         => 'SpoutletBundle:Country',
                    'empty_value'   => 'All',
                    'property'      => 'name',
                ))
                ->add('from_date_country', 'datetime', array(
                    'widget'    => 'single_text',
                    'attr'      => array(
                        'class' => 'datetime-picker'
                )))
                ->add('to_date_country', 'datetime', array(
                    'widget'    => 'single_text',
                    'attr'      => array(
                        'class' => 'datetime-picker'
                )))
                ->add('region', 'entity', array(
                    'class'         => 'SpoutletBundle:Region',
                    'empty_value'   => 'All',
                    'property'      => 'name',
                ))
                ->add('from_date_region', 'datetime', array(
                    'widget'    => 'single_text',
                    'attr'      => array(
                        'class' => 'datetime-picker'
                )))
                ->add('to_date_region', 'datetime', array(
                    'widget'    => 'single_text',
                    'attr'      => array(
                        'class' => 'datetime-picker'
                )))
                ->getForm();


            if ($request->getMethod() == 'POST') {

                $form->bindRequest($request);
                $formData = $form->getData();
                $session = $request->getSession();

                $countries = $session->get('countries') ? : array();
                $regions = $session->get('regions') ? : array();

                if ($request->request->get('all-countries') == 'true') {
                    $countries = $this->processCountries('all', $session);
                }

                if ($request->request->get('all-regions') == 'true') {
                    $regions = $this->processRegions('all', $session);
                }

                if ($formData['country']) {
                    $countries = $this->processCountries($formData['country'], $session);
                }

                if ($formData['region']) {
                    $regions = $this->processRegions($formData['region'], $session);
                }

                $dateFromCountry    = $formData['from_date_country'] ? : null;
                $dateToCountry      = $formData['to_date_country'] ? : null;
                $countryDates       = array('from' => $dateFromCountry, 'to' => $dateToCountry);

                $countryRange = $dateFromCountry ? 'from '.$dateFromCountry->format('m/d/Y H:i') : '';
                $countryRange .= $dateFromCountry && $dateToCountry ? ' ' : '';
                $countryRange .= $dateToCountry ? 'to '.$dateToCountry->format('m/d/Y H:i') : '';

                $dateFromRegion     = $formData['from_date_region'] ? : null;
                $dateToRegion       = $formData['to_date_region'] ? : null;
                $regionDates        = array('from' => $dateFromRegion, 'to' => $dateToRegion);

                $regionRange = $dateFromRegion ? 'from '.$dateFromRegion->format('m/d/Y H:i') : '';
                $regionRange .= $dateFromRegion && $dateToRegion ? ' ' : '';
                $regionRange .= $dateToRegion ? 'to '.$dateToRegion->format('m/d/Y H:i') : '';
            } else {
                $session = $request->getSession();
                $session->set('countries', null);
                $session->set('regions', null);

                $countries  = array();
                $regions    = array();
                $countryDates    = array('from_date_country' => null, 'to_date_country' => null);
                $regionDates    = array('from_date_region' => null, 'to_date_region' => null);

                $countryRange = "All Time";
                $regionRange = "All Regions";
            }

            $report = $metricManager->createMembershipByCountryAndRegionReport($countries, $regions, $countryDates, $regionDates);

            return $this->render('UserBundle:Metric:membershipByCountryAndRegion.html.twig', array(
                'sitesData'     => $report,
                'form'          => $form->createView(),
                'countryRange'  => $countryRange,
                'regionRange'   => $regionRange,
            ));
        }
    }

    private function processCountries($newCountry, $session)
    {
        $countries = $session->get('countries') ? : array();

        if ($newCountry == 'all' && in_array('all', $countries)) {
            return $countries;
        }

        if ($newCountry instanceof Country) {

            $newCountry = $newCountry->getCode();

            if (in_array($newCountry, $countries)) {
                return $countries;
            }
        }

        $countries[] = $newCountry;

        $session->set('countries', $countries);
        return $countries;
    }

    private function processRegions($newRegion, $session)
    {
        $regions = $session->get('regions') ? : array();

        if ($newRegion == 'all' && in_array('all', $regions)) {
            return $regions;
        }

        if ($newRegion instanceof Region) {

            $newRegion = $newRegion->getId();

            if (in_array($newRegion, $regions)) {
                return $regions;
            }
        }

        $regions[] = $newRegion;

        $session->set('regions', $regions);
        return $regions;
    }
}
