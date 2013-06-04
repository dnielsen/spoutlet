<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller,
    Platformd\SpoutletBundle\Entity\Timeline,
    Platformd\SpoutletBundle\Entity\TimelineRepository,
    Platformd\SpoutletBundle\Form\Type\TimelineType
;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response
;


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

        $form = $this->createForm(new TimelineType(), new Timeline());

        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

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

        $form = $this->createForm(new TimelineType(), $timeline);

        if('POST' === $request->getMethod()) {
            $form->bindRequest($request);

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

        $this->getDoctrine()->getEntityManager()->persist($timeline);
        $this->getDoctrine()->getEntityManager()->flush();
    }

    private function delete(Timeline $timeline)
    {
        $this->getDoctrine()->getEntityManager()->remove($timeline);
        $this->getDoctrine()->getEntityManager()->flush();
    }

    private function addTimelineBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Timelines', array(
            'route' => 'admin_timeline_index'
        ));

        return $this->getBreadcrumbs();
    }
}
