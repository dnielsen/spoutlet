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
        $sweepstakes = $this->getSweepstakesRepo()->findAllWithoutLocaleOrderedByNewest();
        $this->addMetricsBreadcrumbs();

        return array(
            'sweeps' => $sweepstakes,
        );
    }

    /**
     * @Template()
     */
    public function showMetricsAction($id)
    {
        $sweepstakes = $this->getSweepstakesRepo()->find($id);
        if (!$sweepstakes) {
            throw $this->createNotFoundException('No sweeps for id '.$id);
        }

        $this->addMetricsBreadcrumbs();
        $this->getBreadcrumbs()->addChild($sweepstakes->getName());

        $entries = $this->getEntryRepo()->findAllOrderedByNewest($sweepstakes);

        return array(
            'sweep' => $sweepstakes,
            'entries' => $entries,
        );
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
}
