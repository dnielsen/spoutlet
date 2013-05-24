<?php

namespace Platformd\HtmlWidgetBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class HtmlWidgetController extends Controller
{
    public function showAction($slug)
    {
        $widget = $this->getHtmlWidgetManager()->find($slug);

        if (!$widget) {
            if ($this->isGranted('ROLE_SUPER_ADMIN')) {
                return $this->render('HtmlWidgetBundle:HtmlWidget:partialNotFound.html.twig', array(
                    'slug' => $slug,
                ));
            }

            return $this->render('HtmlWidgetBundle:HtmlWidget:show.html.twig', array(
                'content' => '<p>Content not found!</p>',
            ));
        }

        return $this->render('HtmlWidgetBundle:HtmlWidget:show.html.twig', array(
            'content' => $widget->getContent(),
        ));
    }

    public function pageAction($slug)
    {
        $widget = $this->getHtmlWidgetManager()->find($slug);

        if (!$widget) {
            if ($this->isGranted('ROLE_SUPER_ADMIN')) {
                return $this->render('HtmlWidgetBundle:HtmlWidget:pageNotFound.html.twig', array(
                    'slug' => $slug,
                ));
            }

            throw new NotFoundHttpException('Widget "'.$slug.'" does not exist.');
        }

        return $this->render('HtmlWidgetBundle:HtmlWidget:page.html.twig', array(
            'content' => $widget->getContent(),
        ));
    }

    private function getHtmlWidgetManager()
    {
        return $this->get('platformd.model.html_widget_manager');
    }
}
