<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Timeline,
    Platformd\SpoutletBundle\Form\Type\TimelineType
;

use Symfony\Component\HttpFoundation\Request;

/**
 * Admin controller for timelines
 */
class TimelineAdminController extends Controller
{
    public function indexAction()
    {
        $timelines = $this
            ->getDoctrine()
            ->getEntityManager()
            ->getRepository('SpoutletBundle:Timeline')
            ->findBy(array('site' => $this->getCurrentSite()->getId()));

        return $this->render('SpoutletBundle:TimelineAdmin:index.html.twig', array(
            'timelines' => $timelines,
        ));
    }

    public function newAction(Request $request)
    {
        $this->addTimelineBreadcrumb()->addChild('New Timeline');

        $form = $this->createForm(TimelineType::class, new Timeline());

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if($form->isValid()) {
                $this->save($form->getData());
                $this->setFlash('success', 'Your timeline has been created');
                return $this->redirect($this->generateUrl('admin_timeline_index'));
            }
        }

        return $this->render('SpoutletBundle:TimelineAdmin:new.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function editAction(Request $request, $id)
    {
        $this->addTimelineBreadcrumb()->addChild('New Timeline');
        $timeline = $this
            ->getDoctrine()
            ->getEntityManager()
            ->getRepository('SpoutletBundle:Timeline')
            ->find($id);

        if(!$timeline) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(TimelineType::class, $timeline);

        if('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if($form->isValid()) {
                $this->save($form->getData());
                $this->setFlash('success', 'Your timeline has been updated.');
                return $this->redirect($this->generateUrl('admin_timeline_index'));
            }
        }

        return $this->render('SpoutletBundle:TimelineAdmin:edit.html.twig', array(
            'form' => $form->createView(),
            'timeline' => $form->getData(),
        ));

    }

    public function deleteAction(Request $request, $id)
    {
        $timeline = $this
            ->getDoctrine()
            ->getEntityManager()
            ->getRepository('SpoutletBundle:Timeline')
            ->find($id);

        if(!$timeline) {
            throw $this->createNotFoundException();
        }

        $this->delete($timeline);
        $this->setFlash('success', 'Your timeline has been deleted.');
        return $this->redirect($this->generateUrl('admin_timeline_index'));
    }

    private function save(Timeline $timeline)
    {
        $timeline->setAuthor($this->getUser());
        $timeline->setSite($this->getCurrentSite());

        $this->getDoctrine()->getManager()->persist($timeline);
        $this->getDoctrine()->getManager()->flush();
    }

    private function delete(Timeline $timeline)
    {
        $this->getDoctrine()->getManager()->remove($timeline);
        $this->getDoctrine()->getManager()->flush();
    }

    private function addTimelineBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Timelines', array(
            'route' => 'admin_timeline_index'
        ));

        return $this->getBreadcrumbs();
    }
}
