<?php

namespace Platformd\HtmlWidgetBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Platformd\HtmlWidgetBundle\Entity\HtmlWidget;
use Platformd\HtmlWidgetBundle\Form\Type\HtmlWidgetType;
use Symfony\Component\Form\Form;

/**
 * Html Widget admin controller.
 *
 */
class HtmlWidgetAdminController extends Controller
{
    public function indexAction()
    {
        $this->addHtmlWidgetsBreadcrumb();

        $siteManager = $this->getSiteManager();

        return $this->render('HtmlWidgetBundle:HtmlWidgetAdmin:index.html.twig', array(
            'sites' => $siteManager->getSiteChoices()
        ));

    }

    public function listAction($site)
    {
        $this->addHtmlWidgetsBreadcrumb();

        $em     = $this->getDoctrine()->getEntityManager();
        $site   = $em->getRepository('SpoutletBundle:Site')->find($site);

        $widgets = $this->getHtmlWidgetManager()->findAllForSite($site);

        return $this->render('HtmlWidgetBundle:HtmlWidgetAdmin:list.html.twig', array(
            'widgets'   => $widgets,
            'site'      => $site,
        ));
    }

    public function newAction(Request $request, $slug = null)
    {
        $this->addHtmlWidgetsBreadcrumb()->addChild('New Html Widget');

        $widget  = new HtmlWidget();

        if ($slug) {
            $widget->setSlug($slug);
        }

        $form    = $this->createForm(new HtmlWidgetType(), $widget);

        if ($this->processForm($form, $request)) {
            $this->setFlash('success', 'The HTML widget was created!');

            return $this->redirect($this->generateUrl('admin_html_widget_index'));
        }

        return $this->render('HtmlWidgetBundle:HtmlWidgetAdmin:new.html.twig', array(
            'widget' => $widget,
            'form'   => $form->createView(),
        ));
    }

    public function editAction($id, Request $request)
    {
        $this->addHtmlWidgetsBreadcrumb()->addChild('Edit Game Page');

        $em         = $this->getDoctrine()->getEntityManager();
        $widget   = $em->getRepository('HtmlWidgetBundle:HtmlWidget')->find($id);

        if (!$widget) {
            throw $this->createNotFoundException('Unable to find HTML Widget.');
        }

        $form   = $this->createForm(new HtmlWidgetType(), $widget);

        if ($this->processForm($form, $request)) {
            $this->setFlash('success', 'The HTML widget was saved!');

            return $this->redirect($this->generateUrl('admin_html_widget_edit', array(
                'id' => $id,
            )));
        }

        return $this->render('HtmlWidgetBundle:HtmlWidgetAdmin:edit.html.twig', array(
            'widget' => $widget,
            'form'   => $form->createView(),
        ));
    }

    private function processForm(Form $form, Request $request)
    {
        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $widget = $form->getData();

                if (count($widget->getSites()) < 1) {
                    $widget->getSites()->add($this->getCurrentSite());
                }

                $this->getHtmlWidgetManager()->saveWidget($widget);

                return true;
            }
        }

        return false;
    }

    private function addHtmlWidgetsBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Html Widgets', array(
            'route' => 'admin_html_widget_index'
        ));

        return $this->getBreadcrumbs();
    }

    private function getHtmlWidgetManager()
    {
        return $this->get('platformd.model.html_widget_manager');
    }
}
