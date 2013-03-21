<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\SiteTakeover;
use Platformd\SpoutletBundle\Form\Type\SiteTakeoverType;
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;

/**
 * Site Takeover admin controller.
 *
 */
class SiteTakeoverAdminController extends Controller
{
    /**
     * Lists all locales - a gateway to the "list" action
     */
    public function indexAction()
    {
        $this->addTakeoverBreadcrumb();

        return $this->render('SpoutletBundle:SiteTakeoverAdmin:index.html.twig', array(
            'sites' => MultitenancyManager::getSiteChoices()
        ));
    }

    public function listAction($site)
    {
        $this->addTakeoverBreadcrumb();
        $this->addSiteBreadcrumbs($site);

        $em         = $this->getDoctrine()->getEntityManager();
        $site       = $em->getRepository('SpoutletBundle:Site')->findOneBy(array('defaultLocale' => $site));
        $takeovers  = $em->getRepository('SpoutletBundle:SiteTakeover')->findAllForSiteSoonestFirst($site);

        return $this->render('SpoutletBundle:SiteTakeoverAdmin:list.html.twig', array(
            'takeovers' => $takeovers,
            'site'      => $site,
        ));
    }

    public function newAction(Request $request)
    {
        $this->addTakeoverBreadcrumb()->addChild('New Takeover');

        $takeover   = new SiteTakeover();
        $form       = $this->createForm(new SiteTakeoverType(), $takeover);

        if ($this->processForm($form, $request)) {
            $this->setFlash('success', 'The site takeover was created successfully!');
            return $this->redirect($this->generateUrl('admin_takeover_index'));
        }

        return $this->render('SpoutletBundle:SiteTakeoverAdmin:new.html.twig', array(
            'takeover'  => $takeover,
            'form'      => $form->createView()
        ));
    }

    public function editAction($id, Request $request)
    {
        $this->addTakeoverBreadcrumb()->addChild('Edit Takeover');

        $em         = $this->getDoctrine()->getEntityManager();
        $takeover   = $em->getRepository('SpoutletBundle:SiteTakeover')->find($id);

        if (!$takeover) {
            throw $this->createNotFoundException('Unable to find site takeover.');
        }

        $form   = $this->createForm(new SiteTakeoverType(), $takeover);

        if ($this->processForm($form, $request)) {
            $this->setFlash('success', 'The site takeover was saved successfully!');
            return $this->redirect($this->generateUrl('admin_takeover_index'));
        }

        return $this->render('SpoutletBundle:SiteTakeoverAdmin:edit.html.twig', array(
            'takeover'  => $takeover,
            'form'      => $form->createView(),
        ));
    }

    private function processForm(Form $form, Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $takeover = $form->getData();

                $otherTakeovers = $em->getRepository('SpoutletBundle:SiteTakeover')->findOverlappingTakeovers($takeover);

                if ($otherTakeovers && count($otherTakeovers) > 0) {

                    $linkString = "";

                    foreach ($otherTakeovers as $otherTakeover) {
                        $linkString .= '<br /><span style="margin-left: 40px;"> - <a href="'.$this->generateUrl('admin_takeover_edit', array('id' => $otherTakeover->getId())).'">'.
                            $otherTakeover->getTitle().'</a> ( '.$otherTakeover->getStartsAt()->format('M j, Y - H:i').' - '.$otherTakeover->getEndsAt()->format('M j, Y - H:i').
                            ' ) '.$otherTakeover->getSiteList().'</span>';
                    }

                    $this->setFlash('error', sprintf('You have selected a timeframe which overlaps with the following takeovers:%s', $linkString));
                    return false;
                }

                $em->persist($takeover);
                $em->flush();

                return true;
            }
        }

        return false;
    }

    private function addSiteBreadcrumbs($site)
    {
        if ($site) {

            $this->getBreadcrumbs()->addChild(MultitenancyManager::getSiteName($site), array(
                'route' => 'admin_takeover_list',
                'routeParameters' => array('site' => $site)
            ));
        }

        return $this->getBreadcrumbs();
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    private function addTakeoverBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Takeovers', array(
            'route' => 'admin_takeover_index'
        ));

        return $this->getBreadcrumbs();
    }

}
