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
use Knp\MediaBundle\Util\MediaUtil;

class AdminController extends Controller
{
    public function indexAction()
    {
        if ($this->isGranted('ROLE_JAPAN_ADMIN')) {
            $url = $this->generateUrl('admin_sweepstakes_list', array('site' => 2));
            return $this->redirect($url);
        }

        $this->addSweepstakesBreadcrumb();

        return $this->render('SweepstakesBundle:Admin:index.html.twig', array(
            'sites' => $this->getSiteManager()->getSiteChoices()
        ));
    }

    public function listAction($site)
    {
        if ($this->isGranted('ROLE_JAPAN_ADMIN')) {
            $site = 2;
        }

        $this->addSweepstakesBreadcrumb();
        $this->addSiteBreadcrumbs($site);

        $em = $this->getDoctrine()->getEntityManager();

        $site = $em->getRepository('SpoutletBundle:Site')->find($site);

        $sweepstakess = $this->getSweepstakesRepo()->findAllForSite($site);

        return $this->render('SweepstakesBundle:Admin:list.html.twig', array(
            'sweepstakess'  => $sweepstakess,
            'site'          => $site,
        ));
    }

    public function newAction(Request $request)
    {
        $this->addSweepstakesBreadcrumb()->addChild('New');

        $tagManager    = $this->getTagManager();
    	$sweepstakes   = new Sweepstakes();

    	$form = $this->createForm(new SweepstakesAdminType($sweepstakes, $tagManager), $sweepstakes);

    	if($request->getMethod() == 'POST') {
    		$form->bindRequest($request);

    		if($form->isValid()) {
    			$this->saveSweepstakes($form);
                return $this->redirect($this->generateUrl('admin_sweepstakes_edit', array('id' => $sweepstakes->getId())));
    		}
    	}

    	return $this->render('SweepstakesBundle:Admin:new.html.twig', array(
            'form'        => $form->createView(),
            'sweepstakes' => $sweepstakes,
            'group'       => null,
        ));
    }

    public function editAction(Request $request, $id)
    {
        $this->addSweepstakesBreadcrumb()->addChild('Edit');

        $tagManager     = $this->getTagManager();
        $sweepstakes    = $this->getSweepstakesRepo()->findOneById($id);

        if (!$sweepstakes) {
            throw $this->createNotFoundException('No sweepstakes for that id');
        }

        $tagManager->loadTagging($sweepstakes);

        $originalQuestions = array();

        // Create an array of the current Tag objects in the database
        foreach ($sweepstakes->getQuestions() as $question) {
            $originalQuestions[] = $question;
        }

        $form = $this->createForm(new SweepstakesAdminType($sweepstakes, $tagManager), $sweepstakes);

        if($request->getMethod() == 'POST') {
        	$form->bindRequest($request);

        	if($form->isValid()) {
        		$this->saveSweepstakes($form, $originalQuestions);
        		return $this->redirect($this->generateUrl('admin_sweepstakes_edit', array('id' => $sweepstakes->getId())));
        	}
        }

        $group = $sweepstakes->getGroup();

    	return $this->render('SweepstakesBundle:Admin:edit.html.twig', array(
            'form'        => $form->createView(),
            'sweepstakes' => $sweepstakes,
            'group'       => $group,
            'hasEntries'  => $sweepstakes->getEntries()->first() !== false,
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
     * Shows key sweepstakes metrics
     * @Template()
     * @return array
     */
    public function metricsAction(Request $request)
    {
        $this->addMetricsBreadcrumbs();

        $em = $this->getDoctrine()->getEntityManager();
        $site = $this->isGranted('ROLE_JAPAN_ADMIN') ? $em->getRepository('SpoutletBundle:Site')->find(2) : null;

        $regionCounts = $this->getEntryRepo()->getRegionCounts($site);
        $totalCounts  = $this->getEntryRepo()->getTotalEntryCounts($site);

        $data                 = array();
        $regionAssignedCounts = array();

        foreach ($regionCounts as $regionCount) {
            $data[$regionCount['sweepstakesId']]['name'] = $regionCount['sweepstakesName'];
            $data[$regionCount['sweepstakesId']]['sites'][$regionCount['regionName']] = $regionCount['entryCount'];

            if (isset($regionAssignedCounts[$regionCount['sweepstakesId']])) {
                $regionAssignedCounts[$regionCount['sweepstakesId']] += $regionCount['entryCount'];
            } else {
                $regionAssignedCounts[$regionCount['sweepstakesId']] = $regionCount['entryCount'];
            }
        }

        foreach ($totalCounts as $count) {
            $data[$count['sweepstakesId']]['total'] = $count['entryCount'];
        }

        $sites = $this->container->get('platformd.metric_manager')->getSiteRegions();

        return array(
            'metrics' => $data,
            'sites'   => $sites,
            'regionAssignedCounts' => $regionAssignedCounts,
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

        return $this->generateMetricsCsvResponse($entries, $sweepstakes->getSlug(), $region);
    }

    /**
     * Downloads a CSV of the entries for a particular sweepstakes
     */
    private function generateMetricsCsvResponse($entries, $sweepstakesSlug, $region)
    {
        // generate CSV content from the rows of data
        $factory = new CsvResponseFactory();

        $factory->addRow(array(
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
            'Question 1',
            'Question 2',
            'Question 3',
            'Question 4',
            'Question 5',
        ));

        foreach ($entries as $entry) {

            $answers = array();

            foreach ($entry[0]->getAnswers() as $answer) {
                $answers[] = $answer->getContent();
            }

            $rowData = array_merge(array(
                $entry[0]->getUser()->getUsername(),
                $entry[0]->getUser()->getId(),
                $entry[0]->getUser()->getFirstName(),
                $entry[0]->getUser()->getLastName(),
                $entry[0]->getUser()->getEmail(),
                $entry[0]->getUser()->getAge(),
                $entry['regionName'] ?: 'None',
                $entry[0]->getCountry()->getName(),
                $entry[0]->getUser()->getState(),
                $entry[0]->getUser()->getCreated()->format('Y-m-d'),
                ($entry[0]->getUser()->getLastLogin()) ? $entry[0]->getUser()->getLastLogin()->format('Y-m-d') : '',
                $entry[0]->getIpAddress(),
            ), $answers);

            $factory->addRow($rowData);
        }

        $filename = sprintf('%s-%s-%s.csv', $sweepstakesSlug, date('Y-m-d'), $region);
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

    private function saveSweepstakes(Form $sweepstakesForm, $originalQuestions = array())
    {
        $em          = $this->getDoctrine()->getEntityManager();
        $sweepstakes = $sweepstakesForm->getData();

        $groupId = $sweepstakesForm['group']->getData();
        if($groupId) {
            $group = $em->getRepository('GroupBundle:Group')->find($groupId);

            if($group) {
                $sweepstakes->setGroup($group);
            }
        }

        $tagManager = $this->getTagManager();
        $tags       = $tagManager->loadOrCreateTags($tagManager->splitTagNames($sweepstakesForm['tags']->getData()));

        $sweepstakes->getId() ? $tagManager->replaceTags($tags, $sweepstakes) : $tagManager->addTags($tags, $sweepstakes);

        $mUtil = new MediaUtil($this->getDoctrine()->getEntityManager());

        if (!$mUtil->persistRelatedMedia($sweepstakes->getBackgroundImage())) {
            $sweepstakes->setBackgroundImage(null);
        }

        foreach ($sweepstakes->getQuestions() as $question) {
            foreach ($originalQuestions as $key => $toDel) {
                if ($toDel->getId() === $question->getId()) {
                    unset($originalQuestions[$key]);
                }
            }
        }

        // remove the relationship between the question and the sweepstakes
        foreach ($originalQuestions as $question) {
            $em->remove($question);
        }

        $em->persist($sweepstakes);
        $em->flush();

        $tagManager->saveTagging($sweepstakes);
        $tagManager->loadTagging($sweepstakes);

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
            ->getRepository('SweepstakesBundle:SweepstakesEntry')
        ;
    }

    private function addSiteBreadcrumbs($site)
    {
        if ($site) {

            $this->getBreadcrumbs()->addChild($this->getSiteManager()->getSiteName($site), array(
                'route' => 'admin_sweepstakes_list',
                'routeParameters' => array('site' => $site)
            ));
        }

        return $this->getBreadcrumbs();
    }

    private function getTagManager()
    {
        return $this->get('platformd.tags.model.tag_manager');
    }
}
