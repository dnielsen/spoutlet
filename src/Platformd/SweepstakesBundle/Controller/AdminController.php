<?php

namespace Platformd\SweepstakesBundle\Controller;

use Platformd\SweepstakesBundle\Entity\Sweepstakes;
use Platformd\SweepstakesBundle\Form\Type\SweepstakesAdminType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Platformd\SpoutletBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use DateTime;

class AdminController extends Controller
{
    public function indexAction()
    {
        $this->addSweepstakesBreadcrumb();
        $sweepstakess = $this->getSweepstakesRepo()->findAll();

    	return $this->render('SweepstakesBundle:Admin:index.html.twig',
            array('sweepstakess' => $sweepstakess));
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

    /**
     * Shows key sweepstakes metrics
     * @Template()
     * @return array
     */
    public function metricsAction(Request $request)
    {
        die('todo');
        $sweepstakess = $this->getSweepstakesRepo()->findAllOrderedByNewest();
        $this->getBreadcrumbs()->addChild('Metrics');
        $this->getBreadcrumbs()->addChild('Sweepstakes');

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
        $since = ($range = $select->getData()) ? new DateTime(sprintf('%s days ago', $range)) : null;

        $sweepstakesMetrics = array();
        $metricManager = $this->container->get('platformd.metric_manager');
        foreach($sweepstakess as $sweepstakes) {
            $sweepstakesMetrics[] = $metricManager->createSweepstakessReport($sweepstakes, $since);
        }

        return array(
            'metrics' => $sweepstakesMetrics,
            'sites'   => $metricManager->getSites(),
            'select'  => $select->createView()
        );
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

        $this
            ->get('platformd.events_manager')
            ->save($sweepstakes);
            
        $this->setFlash('success', 'platformd.sweepstakes.admin.saved');
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
}
