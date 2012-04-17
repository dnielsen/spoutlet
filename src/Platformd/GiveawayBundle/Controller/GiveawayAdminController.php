<?php

namespace Platformd\GiveawayBundle\Controller;

use Platformd\GiveawayBundle\Entity\Giveaway;
use Platformd\GiveawayBundle\Form\Type\GiveawayType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Platformd\SpoutletBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use DateTime;
use Platformd\SpoutletBundle\Util\CsvResponseFactory;

class GiveawayAdminController extends Controller
{
    public function indexAction()
    {
        $this->addGiveawayBreadcrumb();
        $giveaways = $this->getGiveawayRepo()->findAllWithoutLocaleOrderedByNewest();

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
    
    /**
     * Export CSV file of user signed up for specified giveaway
     * 
     * @param int $giveaway
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function exportAction($giveaway)
    { 	
    	// get the giveaway pool data
    	$ga = $this->getDoctrine()->getRepository('GiveawayBundle:GiveawayPool')->findOneBy(array('giveaway' => $giveaway));
       
    	$users = $this->getDoctrine()->getRepository('UserBundle:User');

    	// get uses that signed up for specified giveaway
    	$userlist = $users->findAssignedToUser($ga->getID(), $this->getLocale());

    	return $this->generateGiveawayCsvResponse($userlist, 'giveaway');
    }
    
    /**
     * Downloads a CSV of the entries for a particular giveaway
     */
    private function generateGiveawayCsvResponse($giveaways, $giveawaySlug)
    {
    	// generate CSV content from the rows of data
    	$factory = new CsvResponseFactory();
    
    	$factory->addRow(array(
    			'ID',
    			'First Name',
    			'Last Name',
    			'Email',
    			'IP Address',
    			'Assigned Date',
    	));
    
    	foreach ($giveaways as $entry) {
    		$factory->addRow(array(
    				$entry['id'],
    				$entry['firstname'],
    				$entry['lastname'],
    				$entry['email'],
    			    $entry['ipAddress'],
    				$entry['assignedAt'],		
    		));    		
    	}

    	$filename = sprintf('%s-%s.csv', $giveawaySlug, date('Y-m-d'));
    	return $factory->createResponse($filename);
    
    }
    
    public function editAction(Request $request, $id)
    {
        $this->addGiveawayBreadcrumb()->addChild('Edit');
        $giveaway = $this->getGiveawayRepo()->findOneById($id);

        if (!$giveaway) {
            throw $this->createNotFoundException('No giveaway for that id');
        }

        $this->setupEmptyRedemptionInstructions($giveaway);

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
    public function metricsAction(Request $request)
    {
        $giveaways = $this->getGiveawayRepo()->findAllOrderedByNewest();
        $this->getBreadcrumbs()->addChild('Metrics');
        $this->getBreadcrumbs()->addChild('Giveaways');

        $filterForm = $this->createFormBuilder(array(), array('csrf_protection' => false))
            ->add('results_range', 'choice', array(
                'choices' => array(
                    '7'  => 'Last 7 days',
                    '30' => 'Last 30 days',
                    ''   => 'All time',
                ))
            )
            ->add('status', 'choice', array(
                'choices' => array(
                    1 => 'Totally Enabled Giveaways',
                    0 => 'Disabled Giveaways',
                )
            ))
            ->getForm()
        ;

        // default filtering stuff
        $since = null;
        $onlyEnabled = true;

        $requestData = $request->query->get($filterForm->getName());
        if (!empty($requestData)) {
            $filterForm->bindRequest($request);
            if ($filterForm->isValid()) {
                $data = $filterForm->getData();
                $since = ($range = $data['results_range']) ? new DateTime(sprintf('%s days ago', $range)) : null;

                $onlyEnabled = ($data['status'] == 1);
            }
        }

        $giveawayMetrics = array();
        $metricManager = $this->container->get('platformd.metric_manager');
        foreach($giveaways as $giveaway) {
            /*
             * Filter results: Skip giveway if
             *     a) We only want enabled giveaways and this giveaway is *not* active/enabled
             *     b) We only want disabled giveaways and this giveaway *is* active/enabled
             */
            if (($onlyEnabled && !$giveaway->isActive()) || (!$onlyEnabled && $giveaway->isActive())) {
                continue;
            }

            $giveawayMetrics[] = $metricManager->createGiveawaysReport($giveaway, $since);
        }

        return array(
            'metrics' => $giveawayMetrics,
            'sites'   => $metricManager->getSites(),
            'form'    => $filterForm->createView()
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
