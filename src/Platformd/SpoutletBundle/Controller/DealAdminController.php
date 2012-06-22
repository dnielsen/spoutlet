<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Deal;
use Platformd\SpoutletBundle\Form\Type\DealType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;

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

        $deals = $this->getDealManager()->findAllForSiteNewestFirst($site);

        return $this->render('SpoutletBundle:DealAdmin:list.html.twig', array(
            'entities' => $deals
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
            $this->setFlash('success', 'The Deal was created!');

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
            $this->setFlash('success', 'The Deal was saved!');

            return $this->redirect($this->generateUrl('admin_deal_edit', array('id' => $id)));
        }

        return $this->render('SpoutletBundle:DealAdmin:edit.html.twig', array(
            'deal'      => $deal,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
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
