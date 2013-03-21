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

        $form = $this->createFormBuilder()
            ->add('country', 'entity', array(
                'class'         => 'SpoutletBundle:Country',
                'empty_value'   => 'Country',
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
                'empty_value'   => 'Region',
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

        $report = $metricManager->createMembershipByCountryReport($countries, $regions, $countryDates, $regionDates);

        return array(
            'sitesData'     => $report,
            'form'          => $form->createView(),
            'countryRange'  => $countryRange,
            'regionRange'   => $regionRange,
        );
    }

    private function processCountries($newCountry, $session)
    {
        $countries = $session->get('countries') ? : array();

        foreach ($countries as $country) {
            if ($country->getName() == $newCountry->getName()) {
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

        foreach ($regions as $region) {
            if ($region->getName() == $newRegion->getName()) {
                return $regions;
            }
        }

        $regions[] = $newRegion;

        $session->set('regions', $regions);
        return $regions;
    }
}
