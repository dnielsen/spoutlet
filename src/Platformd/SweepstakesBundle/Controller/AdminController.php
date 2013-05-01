<?php

namespace Platformd\SweepstakesBundle\Controller;

use Platformd\SweepstakesBundle\Entity\Sweepstakes;
use Platformd\SweepstakesBundle\Form\Type\SweepstakesAdminType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Platformd\SpoutletBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use DateTime;
use Platformd\SpoutletBundle\Util\CsvResponseFactory;
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;

class AdminController extends Controller
{
    public function indexAction()
    {
        if ($this->isGranted('ROLE_JAPAN_ADMIN')) {
            $url = $this->generateUrl('admin_sweepstakes_list', array('site' => 'ja'));
            return $this->redirect($url);
        }

        $this->addSweepstakesBreadcrumb();

        return $this->render('SweepstakesBundle:Admin:index.html.twig', array(
            'sites' => MultitenancyManager::getSiteChoices()
        ));
    }

    public function listAction($site)
    {
        if ($this->isGranted('ROLE_JAPAN_ADMIN')) {
            $site = 'ja';
        }

        $this->addSweepstakesBreadcrumb();
        $this->addSiteBreadcrumbs($site);

        $em = $this->getDoctrine()->getEntityManager();

        $site = $em->getRepository('SpoutletBundle:Site')->findOneBy(array('defaultLocale' => $site));

        $sweepstakess = $this->getSweepstakesRepo()->findAllForSite($site);

        return $this->render('SweepstakesBundle:Admin:list.html.twig', array(
            'sweepstakess'  => $sweepstakess,
            'site'          => $site,
        ));
    }

    public function newAction(Request $request)
    {
        $this->addSweepstakesBreadcrumb()->addChild('New');
    	$sweepstakes = new Sweepstakes();

    	$form = $this->createForm(new SweepstakesAdminType(), $sweepstakes);

    	if($request->getMethod() == 'POST')
    	{
    		$form->bindRequest($request);

    		if($form->isValid())
    		{
    			$this->saveSweepstakes($form);

                // redirect to the "edit" page
    			return $this->redirect($this->generateUrl('admin_sweepstakes_edit', array('id' => $sweepstakes->getId())));
    		}
    	}

    	return $this->render('SweepstakesBundle:Admin:new.html.twig', array(
            'form' => $form->createView(),
            'sweepstakes' => $sweepstakes,
        ));
    }

    public function editAction(Request $request, $id)
    {
        $this->addSweepstakesBreadcrumb()->addChild('Edit');
        $sweepstakes = $this->getSweepstakesRepo()->findOneById($id);

        if (!$sweepstakes) {
            throw $this->createNotFoundException('No sweepstakes for that id');
        }

        $test   = $sweepstakes->getTestOnly();
        if ($test === null) {
            $sweepstakes->setTestOnly(0);
        }

        $form = $this->createForm(new SweepstakesAdminType(), $sweepstakes);

        if($request->getMethod() == 'POST')
        {
        	$form->bindRequest($request);

        	if($form->isValid())
        	{
        		$this->saveSweepstakes($form);

        		return $this->redirect($this->generateUrl('admin_sweepstakes_edit', array('id' => $sweepstakes->getId())));
        	}
        }

    	return $this->render('SweepstakesBundle:Admin:edit.html.twig',
    		array('form' => $form->createView(), 'sweepstakes' => $sweepstakes));
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
     * Shows key sweepstakes metrics
     * @Template()
     * @return array
     */
    public function metricsAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $site = $this->isGranted('ROLE_JAPAN_ADMIN') ? $em->getRepository('SpoutletBundle:Site')->find(2) : null;

        $sweepstakes = $site ? $this->getSweepstakesRepo()->findAllForSite($site) : $this->getSweepstakesRepo()->findAllWithoutLocaleOrderedByNewest();

        $this->addMetricsBreadcrumbs();

        return array(
            'sweeps' => $sweepstakes,
        );
    }

    /**
     * @Template()
     */
    public function showMetricsAction($id, Request $request)
    {
        $sweepstakes = $this->getSweepstakesRepo()->find($id);
        if (!$sweepstakes) {
            throw $this->createNotFoundException('No sweeps for id '.$id);
        }

        $entries = $this->getEntryRepo()->findAllOrderedByNewest($sweepstakes);

        // we support CSV!
        if ($request->getRequestFormat() == 'csv') {
            return $this->generateMetricsCsvResponse($entries, $sweepstakes->getSlug());
        }

        $this->addMetricsBreadcrumbs();
        $this->getBreadcrumbs()->addChild($sweepstakes->getName());

        return array(
            'sweep' => $sweepstakes,
            'entries' => $entries,
        );
    }

    /**
     * Downloads a CSV of the entries for a particular sweepstakes
     */
    private function generateMetricsCsvResponse($entries, $sweepstakesSlug)
    {
        // generate CSV content from the rows of data
        $factory = new CsvResponseFactory();

        $factory->addRow(array(
            'Username',
            'Id',
            'Email',
            'Acct Created',
            'Last Logged In',
            'First Name',
            'Last Name',
            'Age',
            'Country',
            'State/Province',
            'Ip Address',
        ));

        foreach ($entries as $entry) {
            $factory->addRow(array(
                $entry->getUser()->getUsername(),
                $entry->getUser()->getId(),
                $entry->getUser()->getEmail(),
                $entry->getUser()->getCreated()->format('Y-m-d'),
                ($entry->getUser()->getLastLogin()) ? $entry->getUser()->getLastLogin()->format('Y-m-d') : '',
                $entry->getUser()->getFirstName(),
                $entry->getUser()->getLastName(),
                $entry->getUser()->getAge(),
                $entry->getUser()->getCountry(),
                $entry->getUser()->getState(),
                $entry->getIpAddress(),
            ));
        }

        $filename = sprintf('%s-%s.csv', $sweepstakesSlug, date('Y-m-d'));
        return $factory->createResponse($filename);

    }

    private function addMetricsBreadcrumbs()
    {
        $this->getBreadcrumbs()->addChild('Metrics');
        $this->getBreadcrumbs()->addChild('Sweepstakes', array(
            'route' => 'admin_sweepstakes_metrics',
        ));

        return $this->getBreadcrumbs();
    }

    protected function retrieveSweepstakes($id)
    {
        if (!$sweepstakes = $this->getSweepstakesRepo()->find($id)) {
            throw $this->createNotFoundException();
        }

        return $sweepstakes;
    }

    private function saveSweepstakes(Form $sweepstakesForm)
    {
        // save to db
        $sweepstakes = $sweepstakesForm->getData();

        $ruleset    = $sweepstakes->getRuleset();
        $rules      = $ruleset->getRules();

        $newRulesArray = array();

        $defaultAllow = true;

        foreach ($rules as $rule) {
            if ($rule->getMinAge() || $rule->getMaxAge() || $rule->getCountry()) {
                $rule->setRuleset($ruleset);
                $newRulesArray[] = $rule;

                $defaultAllow = $rule->getRuleType() == "allow" ? false : true;
            }
        }

        $em = $this->getDoctrine()->getEntityManager();
        $oldRules = $em->getRepository('SpoutletBundle:CountryAgeRestrictionRule')->findBy(array('ruleset' => $ruleset->getId()));

        if ($oldRules) {
            foreach ($oldRules as $oldRule) {
                if (!in_array($oldRule, $newRulesArray)) {
                    $oldRule->setRuleset(null);
                }
            }
        }

        $sweepstakes->getRuleset()->setParentType('sweepstake');
        $sweepstakes->getRuleset()->setDefaultAllow($defaultAllow);

        $this
            ->get('platformd.events_manager')
            ->save($sweepstakes);

        $this->setFlash('success', 'Sweepstakes Saved');
    }

    private function getEntityManager()
    {
        return $this->getDoctrine()
            ->getEntityManager();
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    private function addSweepstakesBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Sweepstakes', array(
            'route' => 'admin_sweepstakes_index'
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
            ->getRepository('SweepstakesBundle:Entry')
        ;
    }

    private function addSiteBreadcrumbs($site)
    {
        if ($site) {

            $this->getBreadcrumbs()->addChild(MultitenancyManager::getSiteName($site), array(
                'route' => 'admin_sweepstakes_list',
                'routeParameters' => array('site' => $site)
            ));
        }

        return $this->getBreadcrumbs();
    }
}
