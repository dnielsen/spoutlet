<?php

namespace Platformd\GiveawayBundle\Controller;

use Platformd\GiveawayBundle\Entity\Giveaway;
use Platformd\GiveawayBundle\Form\Type\GiveawayType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Platformd\SpoutletBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;

class GiveawayAdminController extends Controller
{
    public function indexAction()
    {
        $this->addGiveawayBreadcrumb();
        $giveaways = $this->getGiveawayRepo()->findAll();

    	return $this->render('GiveawayBundle:GiveawayAdmin:index.html.twig',
            array('giveaways' => $giveaways));
    }

    public function newAction(Request $request)
    {
        $this->addGiveawayBreadcrumb()->addChild('New');
    	$giveaway = new Giveaway();

        // guarantee we have at least 5 open giveaway boxes
        $this->setupEmptyRedemptionInstructions($giveaway);

    	$form = $this->createForm(new GiveawayType(), $giveaway);

    	if($request->getMethod() == 'POST')
    	{
    		$form->bindRequest($request);

    		if($form->isValid())
    		{
    			$this->saveGiveaway($form);

                // redirect to the "new pool" page
    			return $this->redirect($this->generateUrl('admin_giveaway_pool_new', array('giveaway' => $giveaway->getId())));
    		}
    	}

    	return $this->render('GiveawayBundle:GiveawayAdmin:new.html.twig', array(
            'form' => $form->createView(),
            'giveaway' => $giveaway,
        ));
    }

    public function editAction(Request $request, $id)
    {
        $this->addGiveawayBreadcrumb()->addChild('Edit');
        $giveaway = $this->getGiveawayRepo()->findOneById($id);
        $this->setupEmptyRedemptionInstructions($giveaway);

        if (!$giveaway) {
            throw $this->createNotFoundException('No giveaway for that id');
        }

        $form = $this->createForm(new GiveawayType(), $giveaway);

        if($request->getMethod() == 'POST')
        {
        	$form->bindRequest($request);

        	if($form->isValid())
        	{
        		$this->saveGiveaway($form);
        		return $this->redirect($this->generateUrl('admin_giveaway_edit', array('id' => $giveaway->getId())));
        	}
        }

    	return $this->render('GiveawayBundle:GiveawayAdmin:edit.html.twig',
    		array('form' => $form->createView(), 'giveaway' => $giveaway));
    }

    /**
     * Shows key giveaway metrics
     * @Template()
     * @return array
     */
    public function metricsAction()
    {
        $giveaways = $this->getGiveawayRepo()->findAll();
        $this->getBreadcrumbs()->addChild('Metrics');
        $this->getBreadcrumbs()->addChild('Giveaways');

        $giveawayMetrics = array();
        $metricManager = $this->container->get('giveaway.metric_manager');
        foreach($giveaways as $giveaway) {
            $giveawayMetrics[] = $metricManager->createGiveawaysReport($giveaway);
        }

        return array(
            'metrics' => $giveawayMetrics,
        );
    }

    protected function retrieveGiveaway($id)
    {
        if (!$giveaway = $this->getGiveawayRepo()->find($id)) {
            throw $this->createNotFoundException();
        }

        return $giveaway;
    }

    private function setupEmptyRedemptionInstructions(Giveaway $giveaway)
    {
        $instructions = $giveaway->getRedemptionInstructionsArray();
        while (count($instructions) < 5) {
            $instructions[] = '';
        }

        $giveaway->setRedemptionInstructionsArray($instructions);
    }

    private function saveGiveaway(Form $giveawayForm)
    {
        // save to db
        $giveaway = $giveawayForm->getData();

        $this
            ->get('platformd.events_manager')
            ->save($giveaway);
            
        $this->setFlash('success', 'platformd.giveaway.admin.saved');
    }

    private function getEntityManager()
    {
        return $this->getDoctrine()
            ->getEntityManager();
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    private function addGiveawayBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Giveaways', array(
            'route' => 'admin_giveaway_index'
        ));

        return $this->getBreadcrumbs();
    }
}
