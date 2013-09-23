<?php

namespace Platformd\SweepstakesBundle\Controller;

use Platformd\SweepstakesBundle\Entity\Sweepstakes;
use Platformd\SweepstakesBundle\Entity\SweepstakesQuestion;
use Platformd\SweepstakesBundle\Form\Type\SweepstakesAdminType;
use Platformd\SpoutletBundle\Controller\Controller;
use Platformd\SpoutletBundle\Util\CsvResponseFactory;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;

use DateTime;

use Knp\MediaBundle\Util\MediaUtil;

class AdminController extends Controller
{
    public function indexAction($type)
    {
        $route = $type == Sweepstakes::SWEEPSTAKES_TYPE_PROMO_CODE ? 'admin_promo_code_contest_list' : 'admin_sweepstakes_list';
        $title = $this->getTypeText($type);

        if ($this->isGranted('ROLE_JAPAN_ADMIN')) {
            $url = $this->generateUrl($route, array('site' => 2));
            return $this->redirect($url);
        }

        $this->addSweepstakesBreadcrumb($type);

        return $this->render('SweepstakesBundle:Admin:index.html.twig', array(
            'sites' => $this->getSiteManager()->getSiteChoices(),
            'route' => $route,
            'type'  => $type,
        ));
    }

    public function listAction($site, $type)
    {
        if ($this->isGranted('ROLE_JAPAN_ADMIN')) {
            $site = 2;
        }

        $this->addSweepstakesBreadcrumb($type);
        $this->addSiteBreadcrumbs($site, $type);

        $em = $this->getDoctrine()->getEntityManager();

        $site = $em->getRepository('SpoutletBundle:Site')->find($site);

        $sweepstakess = $this->getSweepstakesRepo()->findAllForSite($site, $type);

        return $this->render('SweepstakesBundle:Admin:list.html.twig', array(
            'sweepstakess' => $sweepstakess,
            'site'         => $site,
            'type'         => $type,
        ));
    }

    public function newAction(Request $request, $type)
    {
        $type = in_array($type, Sweepstakes::getValidTypes()) ? $type : Sweepstakes::SWEEPSTAKES_TYPE_SWEEPSTAKES;
        $isPromo = $type == Sweepstakes::SWEEPSTAKES_TYPE_PROMO_CODE;

        $this->addSweepstakesBreadcrumb($type)->addChild('New');

        $tagManager    = $this->getTagManager();
        $sweepstakes   = new Sweepstakes();

        if ($isPromo) {
            $question   = new SweepstakesQuestion();

            $question->setContent($this->trans('sweepstakes.promocode.question'));
            $sweepstakes->addSweepstakesQuestion($question);
            $sweepstakes->setEventType(Sweepstakes::SWEEPSTAKES_TYPE_PROMO_CODE);
        }

        $form = $this->createForm(new SweepstakesAdminType($sweepstakes, $this->getSweepstakesManager(), $tagManager), $sweepstakes);

        if($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if($form->isValid()) {
                $this->saveSweepstakes($form);

                $route = $isPromo ? 'admin_promo_code_contest_edit' : 'admin_sweepstakes_edit';
                return $this->redirect($this->generateUrl($route, array('id' => $sweepstakes->getId())));
            }
        }

        return $this->render('SweepstakesBundle:Admin:new.html.twig', array(
            'form'        => $form->createView(),
            'sweepstakes' => $sweepstakes,
            'group'       => null,
            'type'        => $type,
        ));
    }

    public function editAction(Request $request, $id, $type)
    {
        $isPromo = $type == Sweepstakes::SWEEPSTAKES_TYPE_PROMO_CODE;

        $this->addSweepstakesBreadcrumb($type)->addChild('Edit');

        $tagManager     = $this->getTagManager();
        $sweepstakes    = $this->getSweepstakesRepo()->findOneById($id);

        if (!$sweepstakes) {
            throw $this->createNotFoundException('No sweepstakes for that id');
        }

        $tagManager->loadTagging($sweepstakes);

        $originalQuestions = array();

        if ($type == Sweepstakes::SWEEPSTAKES_TYPE_SWEEPSTAKES) {
            // Create an array of the current Tag objects in the database
            foreach ($sweepstakes->getQuestions() as $question) {
                $originalQuestions[] = $question;
            }
        }

        $form = $this->createForm(new SweepstakesAdminType($sweepstakes, $this->getSweepstakesManager(), $tagManager), $sweepstakes);

        if($request->getMethod() == 'POST') {
        	$form->bindRequest($request);

        	if($form->isValid()) {
        		$this->saveSweepstakes($form, $originalQuestions);

                $route = $isPromo ? 'admin_promo_code_contest_edit' : 'admin_sweepstakes_edit';
        		return $this->redirect($this->generateUrl($route, array('id' => $sweepstakes->getId())));
        	}
        }

        $group = $sweepstakes->getGroup();

    	return $this->render('SweepstakesBundle:Admin:edit.html.twig', array(
            'form'        => $form->createView(),
            'sweepstakes' => $sweepstakes,
            'group'       => $group,
            'type'        => $type,
        ));
    }

    public function approveAction($id)
    {
        $sweeps = $this->retrieveSweepstakes($id);
        $sweeps->setPublished(true);
        $this->getEntityManager()->flush();

        $this->setFlash('success', 'The sweepstakes has been published');

        return $this->redirect($this->generateUrl('admin_sweepstakes_index'));
    }

    public function unpublishAction($id)
    {
        $sweeps = $this->retrieveSweepstakes($id);
        $sweeps->setPublished(false);

        $this->getEntityManager()->flush();

        $this->setFlash('success', 'The sweepstakes has been unpublished');

        return $this->redirect($this->generateUrl('admin_sweepstakes_index'));
    }

    /**
     * @Template()
     */
    public function metricsAction(Request $request, $type)
    {
        $this->addMetricsBreadcrumbs($type);

        $em          = $this->getDoctrine()->getEntityManager();
        $site        = $this->isGranted('ROLE_JAPAN_ADMIN') ? $em->getRepository('SpoutletBundle:Site')->find(2) : null;

        $type = in_array($type, Sweepstakes::getValidTypes()) ? $type : Sweepstakes::SWEEPSTAKES_TYPE_SWEEPSTAKES;

        $allSweeps   = $this->getSweepstakesRepo()->findAllForSite($site, $type);
        $sweepstakes = array();

        foreach ($allSweeps as $sweeps) {
            $sweepstakes[$sweeps->getId()] = $sweeps->getName();
        }

        $regionCounts = $this->getEntryRepo()->getRegionCounts($site);
        $totalCounts  = $this->getEntryRepo()->getTotalEntryCounts($site);

        $data                 = array();
        $regionAssignedCounts = array();

        foreach ($totalCounts as $count) {
            $data[$count['sweepstakesId']]['name']  = $count['sweepstakesName'];
            $data[$count['sweepstakesId']]['total'] = $count['entryCount'];
            $data[$count['sweepstakesId']]['sites'] = array();
        }

        foreach ($regionCounts as $regionCount) {
            $data[$regionCount['sweepstakesId']]['sites'][$regionCount['regionName']] = $regionCount['entryCount'];

            if (isset($regionAssignedCounts[$regionCount['sweepstakesId']])) {
                $regionAssignedCounts[$regionCount['sweepstakesId']] += $regionCount['entryCount'];
            } else {
                $regionAssignedCounts[$regionCount['sweepstakesId']] = $regionCount['entryCount'];
            }
        }

        $sites = $this->container->get('platformd.metric_manager')->getRegions();

        $csvRoute = $type == Sweepstakes::SWEEPSTAKES_TYPE_SWEEPSTAKES ? 'admin_sweepstakes_metrics_csv' : 'admin_promo_code_contest_metrics_csv';

        return array(
            'metrics'              => $data,
            'sites'                => $sites,
            'sweepstakes'          => $sweepstakes,
            'regionAssignedCounts' => $regionAssignedCounts,
            'csvRoute'             => $csvRoute,
            'type'                 => $type,
        );
    }

    /**
     * @Template()
     */
    public function getMetricsAction($id, $region, Request $request)
    {
        $sweepstakes = $this->getSweepstakesRepo()->find($id);
        if (!$sweepstakes) {
            throw $this->createNotFoundException('No sweeps for id '.$id);
        }

        if ($region == 'all') {
            $entries = $this->getEntryRepo()->findAllOrderedByNewest($sweepstakes);
        } elseif ($region == 'global') {
            $entries = $this->getEntryRepo()->findAllWithoutRegionOrderedByNewest($sweepstakes);
        } else {
            $regionId = $region;
            $region   = $this->getDoctrine()->getEntityManager()->getRepository('SpoutletBundle:Region')->find($regionId);

            if (!$region) {
                throw $this->createNotFoundException('No region for id '.$regionId);
            }

            $region = $region->getName();

            $entries = $this->getEntryRepo()->findAllForRegionOrderedByNewest($sweepstakes, $regionId);
        }

        return $this->generateMetricsCsvResponse($sweepstakes, $entries, $region);
    }

    /**
     * Downloads a CSV of the entries for a particular sweepstakes
     */
    private function generateMetricsCsvResponse($sweepstakes, $entries, $region)
    {
        // generate CSV content from the rows of data
        $factory = new CsvResponseFactory();

        $questions = array();

        foreach ($sweepstakes->getQuestions() as $question) {
            $questions[] = $question->getContent();
        }

        if ($sweepstakes->getHasOptionalCheckbox()) {
            $questions[] = 'Additional Checkbox';
        }

        $factory->addRow(array_merge(array(
            'Username',
            'Id',
            'First Name',
            'Last Name',
            'Email',
            'Age',
            'Region',
            'Country',
            'State/Province',
            'Acct Created',
            'Last Logged In',
            'IP Address',
            'Created Account to Enter',
            'Phone Number',
        ), $questions));

        foreach ($entries as $entry) {

            $answers = array();

            foreach ($entry[0]->getAnswers() as $answer) {
                $answers[] = $answer->getContent();
            }

            if ($sweepstakes->getHasOptionalCheckbox()) {
                $answers[] = $entry[0]->getOptionalCheckboxAnswer() ? 'Y' : 'N';
            }

            $rowData = array_merge(array(
                $entry[0]->getUser()->getUsername(),
                $entry[0]->getUser()->getId(),
                $entry[0]->getUser()->getFirstName(),
                $entry[0]->getUser()->getLastName(),
                $entry[0]->getUser()->getEmail(),
                $entry[0]->getUser()->getAge(),
                $entry['regionName'] ?: 'None',
                $entry[0]->getCountry() ? $entry[0]->getCountry()->getName() : null,
                $entry[0]->getUser()->getState(),
                $entry[0]->getUser()->getCreated()->format('Y-m-d'),
                ($entry[0]->getUser()->getLastLogin()) ? $entry[0]->getUser()->getLastLogin()->format('Y-m-d') : '',
                $entry[0]->getIpAddress(),
                $entry[0]->getCreatedAccount() ? 'Y' : 'N',
                $entry[0]->getPhoneNumber(),
            ), $answers);

            $factory->addRow($rowData);
        }

        $filename = sprintf('%s-%s-%s.csv', $sweepstakes->getSlug(), date('Y-m-d'), $region);
        return $factory->createResponse($filename);

    }

    private function addMetricsBreadcrumbs($type=Sweepstakes::SWEEPSTAKES_TYPE_SWEEPSTAKES)
    {
        $this->getBreadcrumbs()->addChild('Metrics');
        $this->getBreadcrumbs()->addChild($type == Sweepstakes::SWEEPSTAKES_TYPE_PROMO_CODE ? 'Promo Code Contests' : 'Sweepstakes');

        return $this->getBreadcrumbs();
    }

    protected function retrieveSweepstakes($id)
    {
        if (!$sweepstakes = $this->getSweepstakesRepo()->find($id)) {
            throw $this->createNotFoundException();
        }

        return $sweepstakes;
    }

    private function saveSweepstakes(Form $sweepstakesForm, $originalQuestions = array())
    {
        $this->getSweepstakesManager()->saveSweepstakes($sweepstakesForm, $originalQuestions);
    }

    private function getEntityManager()
    {
        return $this->getDoctrine()
            ->getEntityManager();
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    private function addSweepstakesBreadcrumb($type=Sweepstakes::SWEEPSTAKES_TYPE_SWEEPSTAKES)
    {
        $text  = $type == Sweepstakes::SWEEPSTAKES_TYPE_PROMO_CODE ? 'Promo Code Contests' : 'Sweepstakes';
        $route = $type == Sweepstakes::SWEEPSTAKES_TYPE_PROMO_CODE ? 'admin_promo_code_contest_index' : 'admin_sweepstakes_index';

        $this->getBreadcrumbs()->addChild($text, array(
            'route' => $route
        ));

        return $this->getBreadcrumbs();
    }

    /**
     * @return \Platformd\SweepstakesBundle\Entity\EntryRepository
     */
    private function getEntryRepo()
    {
        return $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('SweepstakesBundle:SweepstakesEntry')
        ;
    }

    private function addSiteBreadcrumbs($site, $type=Sweepstakes::SWEEPSTAKES_TYPE_SWEEPSTAKES)
    {
        if ($site) {

            $route = ($type == Sweepstakes::SWEEPSTAKES_TYPE_PROMO_CODE ? 'admin_promo_code_contest_list' : 'admin_sweepstakes_list');

            $this->getBreadcrumbs()->addChild($this->getSiteManager()->getSiteName($site), array(
                'route' => $route,
                'routeParameters' => array('site' => $site)
            ));
        }

        return $this->getBreadcrumbs();
    }

    private function getTagManager()
    {
        return $this->get('platformd.tags.model.tag_manager');
    }

    private function getSweepstakesManager()
    {
        return $this->get('platformd_sweeps.model.sweepstakes_manager');
    }

    private function getTypeText($type)
    {
        return $type == Sweepstakes::SWEEPSTAKES_TYPE_PROMO_CODE ? 'Promo Code Contest' : 'Sweepstakes';
    }
}
