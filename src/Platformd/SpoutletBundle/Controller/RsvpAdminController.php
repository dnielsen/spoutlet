<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Platformd\SpoutletBundle\Entity\Rsvp;
use Platformd\SpoutletBundle\Entity\RsvpCode;
use Platformd\SpoutletBundle\Form\Type\RsvpType;
use Symfony\Component\HttpFoundation\Request;
use Platformd\SpoutletBundle\Util\CsvResponseFactory;

class RsvpAdminController extends Controller
{
    public function indexAction()
    {
        $this->addRsvpBreadcrumb();

        $rsvps = $this->get('doctrine')->getRepository('SpoutletBundle:Rsvp')->findAll();

        return $this->render('SpoutletBundle:RsvpAdmin:index.html.twig', array(
            'rsvps' => $rsvps,
        ));
    }

    public function newAction(Request $request)
    {
        $this->addRsvpBreadcrumb()->addChild('New Rsvp');

        $form = $this->createForm(new RsvpType, new Rsvp);

        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $this->save($form->getData());
                $this->setFlash('success', 'RSVP created');

                return $this->redirect($this->generateUrl('admin_rsvp_edit', array('id' => $form->getData()->getId())));
            }
        }

        return $this->render('SpoutletBundle:RsvpAdmin:new.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function editAction(Request $request, $id)
    {
        $this->addRsvpBreadcrumb()->addChild('Edit Rsvp');

        $rsvp = $this->getRsvpOr404($id);
        $form = $this->createForm(new RsvpType, $rsvp);

        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $this->save($form->getData());
                $this->setFlash('success', 'RSVP updated');

                return $this->redirect($this->generateUrl('admin_rsvp_edit', array('id' => $form->getData()->getId())));
            }
        }

        return $this->render('SpoutletBundle:RsvpAdmin:edit.html.twig', array(
            'form' => $form->createView(),
            'rsvp' => $form->getData(),
        ));
    }

    public function exportToCsvAction($id)
    {
        $rsvp = $this->getRsvpOr404($id);

        $csvResponseFactory = new CsvResponseFactory;
        $csvResponseFactory->addRow(array(
            'RSVP Date',
            'First Name',
            'Last Name',
            'Phone number',
            'Email Address',
            'RSVP Code',
        ));
        foreach ($rsvp->getAttendees() as $attendee) {
            $csvResponseFactory->addRow(array(
                'RSVP Date' => $rsvp->getCreatedAt()->format('Y-m-d'),
                'First Name' => $attendee->getFirstName(),
                'Last Name' => $attendee->getLastName(),
                'Phone number' => $attendee->getPhoneNumber(),
                'Email Address' => $attendee->getEmail(),
                'RSVP Code' => $attendee->getCode(),
            ));
        }

        return $csvResponseFactory->createResponse(sprintf('rsvp-%s.csv', $rsvp->getId()));
    }

    private function save(Rsvp $rsvp)
    {
        $this->getDoctrine()->getEntityManager()->persist($rsvp);
        $this->getDoctrine()->getEntityManager()->flush();
    }

    private function getRsvpOr404($id)
    {
        $rsvp = $this->get('doctrine')->getRepository('SpoutletBundle:Rsvp')->find($id);

        if (!$rsvp) {
            throw $this->createNotFoundException();
        }

        return $rsvp;
    }

    private function addRsvpBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('RSVP', array(
            'route' => 'admin_rsvp_index'
        ));

        return $this->getBreadcrumbs();
    }
}

