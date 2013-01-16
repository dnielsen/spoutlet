<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Deal;
use Platformd\SpoutletBundle\Form\Type\DealType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;
use DateTime;

/**
 * Deal admin controller.
 *
 */
class DealAdminController extends Controller
{
    /**
     * Lists all locales - a gateway to the "list" action
     */
    public function indexAction()
    {
        $this->addDealsBreadcrumb();

        return $this->render('SpoutletBundle:DealAdmin:index.html.twig', array(
            'sites' => MultitenancyManager::getSiteChoices()
        ));
    }

    /**
     * Lists all Deal entities for the selected site.
     */
    public function listAction($site)
    {
        $this->addDealsBreadcrumb();
        $this->addSiteBreadcrumbs($site);

        $em = $this->getDoctrine()->getEntityManager();

        $site = $em->getRepository('SpoutletBundle:Site')->findOneBy(array('defaultLocale' => $site));

        $deals = $this->getDealManager()->findAllForSiteNewestFirst($site);

        return $this->render('SpoutletBundle:DealAdmin:list.html.twig', array(
            'entities' => $deals,
            'site'     => $site,
        ));
    }

    /**
     * Creates a new Deal.
     *
     */
    public function newAction(Request $request)
    {
        $this->addDealsBreadcrumb()->addChild('New Deal');

        $deal   = new Deal();
        $form   = $this->createForm(new DealType(), $deal);

        if ($this->processForm($form, $request)) {
            $this->setFlash('success', 'The deal was created!');

            return $this->redirect($this->generateUrl('admin_deal_edit', array('id' => $deal->getId())));
        }

        return $this->render('SpoutletBundle:DealAdmin:new.html.twig', array(
            'deal' => $deal,
            'form'   => $form->createView()
        ));
    }

    /**
     * Edits an existing Deal.
     *
     */
    public function editAction($id, Request $request)
    {
        $this->addDealsBreadcrumb()->addChild('Edit Deal');
        $em = $this->getDoctrine()->getEntityManager();

        $deal = $em->getRepository('SpoutletBundle:Deal')->find($id);

        if (!$deal) {
            throw $this->createNotFoundException('Unable to find deal.');
        }

        $editForm   = $this->createForm(new DealType(), $deal);
        $deleteForm = $this->createDeleteForm($id);

        if ($this->processForm($editForm, $request)) {
            $this->setFlash('success', 'The deal was saved!');

            return $this->redirect($this->generateUrl('admin_deal_edit', array('id' => $id)));
        }

        return $this->render('SpoutletBundle:DealAdmin:edit.html.twig', array(
            'deal'      => $deal,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Shows metrics for all the deals
     *
     * @param Request $request
     */
    public function metricsAction(Request $request)
    {
        /** @var $metricManager \Platformd\SpoutletBundle\Metric\MetricManager */
        $metricManager = $this->container->get('platformd.metric_manager');

        $deals = $this->getDealManager()->findAllOrderedByNewest();
        $this->getBreadcrumbs()->addChild('Metrics');
        $this->getBreadcrumbs()->addChild('Deals');

        $filterForm = $metricManager->createFilterFormBuilder($this->get('form.factory'))
            ->add('status', 'choice', array(
            'choices' => array(
                1 => 'Published Deals',
                0 => 'Unpublished Deals',
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

        $dealMetrics = array();
        /** @var $deal \Platformd\SpoutletBundle\Entity\Deal */
        foreach($deals as $deal) {
            /*
             * Filter results: Skip giveway if
             *     a) We only want enabled giveaways and this giveaway is *not* active/enabled
             *     b) We only want disabled giveaways and this giveaway *is* active/enabled
             */
            if (($onlyEnabled && !$deal->isPublished()) || (!$onlyEnabled && $deal->isPublished())) {
                continue;
            }

            $dealMetrics[] = $metricManager->createDealReport($deal, $since);
        }

        return $this->render('SpoutletBundle:DealAdmin:metrics.html.twig', array(
            'metrics' => $dealMetrics,
            'sites'   => $metricManager->getSites(),
            'form'    => $filterForm->createView()
        ));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }

    private function processForm(Form $form, Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {
                /** @var $deal \Platformd\SpoutletBundle\Entity\Deal */
                $deal = $form->getData();

                $ruleset    = $deal->getRuleset();
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

                $oldRules = $em->getRepository('SpoutletBundle:CountryAgeRestrictionRule')->findBy(array('ruleset' => $ruleset->getId()));

                if ($oldRules) {
                    foreach ($oldRules as $oldRule) {
                        if (!in_array($oldRule, $newRulesArray)) {
                            $oldRule->setRuleset(null);
                        }
                    }
                }

                $deal->getRuleset()->setParentType('deal');
                $deal->getRuleset()->setDefaultAllow($defaultAllow);

                $this->getDealManager()->saveDeal($deal);

                return true;
            }
        }

        return false;
    }

    private function addSiteBreadcrumbs($site)
    {
        if ($site) {

            $this->getBreadcrumbs()->addChild(MultitenancyManager::getSiteName($site), array(
                'route' => 'admin_deal_site',
                'routeParameters' => array('site' => $site)
            ));
        }

        return $this->getBreadcrumbs();
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    private function addDealsBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Deals', array(
            'route' => 'admin_deal'
        ));

        return $this->getBreadcrumbs();
    }

    /**
     * @return \Platformd\SpoutletBundle\Model\DealManager
     */
    private function getDealManager()
    {
        return $this->get('platformd.model.deal_manager');
    }
}
