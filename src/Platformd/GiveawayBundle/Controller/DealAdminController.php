<?php

namespace Platformd\GiveawayBundle\Controller;

use Platformd\GiveawayBundle\Entity\Deal;
use Platformd\GiveawayBundle\Form\Type\DealType;
use Platformd\SpoutletBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;
use DateTime;
use Doctrine\ORM\EntityRepository;

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

        return $this->render('GiveawayBundle:DealAdmin:index.html.twig', array(
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

        return $this->render('GiveawayBundle:DealAdmin:list.html.twig', array(
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

            return $this->redirect($this->generateUrl('admin_deal_pool_new', array('dealId' => $deal->getId())));
        }

        return $this->render('GiveawayBundle:DealAdmin:new.html.twig', array(
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

        $deal = $em->getRepository('GiveawayBundle:Deal')->find($id);

        if (!$deal) {
            throw $this->createNotFoundException('Unable to find deal.');
        }

        $test   = $deal->getTestOnly();
        if ($test === null) {
            $deal->setTestOnly(0);
        }

        $editForm   = $this->createForm(new DealType(), $deal);
        $deleteForm = $this->createDeleteForm($id);

        if ($this->processForm($editForm, $request)) {
            $this->setFlash('success', 'The deal was saved!');

            return $this->redirect($this->generateUrl('admin_deal_edit', array('id' => $id)));
        }

        return $this->render('GiveawayBundle:DealAdmin:edit.html.twig', array(
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
        $metricManager = $this->container->get('platformd.metric_manager');

        $this->getBreadcrumbs()->addChild('Metrics');
        $this->getBreadcrumbs()->addChild('Deals');

        $filterForm = $metricManager->createFilterFormBuilder($this->get('form.factory'))
            ->add('deal', 'entity', array(
                'class' => 'GiveawayBundle:Deal',
                'property' => 'name',
                'empty_value' => 'All Deals',
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('d')
                        ->orderBy('d.name', 'ASC');
                },
            ))
            ->getForm()
        ;

        // default filtering stuff
        $from   = null;
        $to     = null;
        $deal   = null;

        $requestData = $request->query->get($filterForm->getName());
        if (!empty($requestData)) {
            $filterForm->bindRequest($request);
            if ($filterForm->isValid()) {
                $data   = $filterForm->getData();

                $from   = $data['startDate'] ? : null;
                $to     = $data['endDate'] ? : null;
                $deal   = $data['deal'] ? : null;
            }
        }

        if ($deal == null) {
            $deals  = $this->getDealManager()->findAllOrderedByNewest();
        } else {
            $deals  = $deal ? array($deal) : $this->getDealManager()->findAllOrderedByNewest();
        }

        $dealMetrics = array();
        /** @var $deal \Platformd\GiveawayBundle\Entity\Deal */
        foreach($deals as $deal) {
            $dealMetrics[] = $metricManager->createDealReport($deal, $from, $to);
        }

        return $this->render('GiveawayBundle:DealAdmin:metrics.html.twig', array(
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
                /** @var $deal \Platformd\GiveawayBundle\Entity\Deal */
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

    private function getDealManager()
    {
        return $this->get('platformd.model.deal_manager');
    }
}
