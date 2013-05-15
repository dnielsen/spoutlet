<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Platformd\SpoutletBundle\Entity\Rsvp;
use Symfony\Component\HttpFoundation\Request;
use Platformd\SpoutletBundle\Form\Type\RsvpAttendeeType;
use Platformd\SpoutletBundle\Entity\RsvpAttendee;

class RsvpController extends Controller
{
    public function showAction($slug)
    {
        $rsvp = $this->getRsvpOr404($slug);

        return $this->render('SpoutletBundle:Rsvp:show.html.twig', array(
            'rsvp' => $rsvp,
        ));
    }

    public function attendAction(Request $request, $slug)
    {
        $rsvp = $this->getRsvpOr404($slug);
        $form = $this->createForm(new RsvpAttendeeType, new RsvpAttendee($rsvp));

        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $this->save($form->getData());
                $this->setFlash('success', $rsvp->getSuccessMessage());

                return $this->redirect($this->generateUrl('rsvp_show', array(
                    'slug' => $rsvp->getSlug()
                )));
            }
        }

        return $this->render('SpoutletBundle:Rsvp:attend.html.twig', array(
            'form' => $form->createView(),
            'rsvp' => $rsvp,
        ));
    }

    private function save(RsvpAttendee $rsvp)
    {
        $this->getDoctrine()->getEntityManager()->persist($rsvp);
        $this->getDoctrine()->getEntityManager()->flush();
    }

    private function getRsvpOr404($slug)
    {
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $criteria = array('slug' => $slug);
        } else {
            $criteria = array(
                'slug' => $slug,
                'isPublished' => true,
            );
        }

        $rsvp = $this->getDoctrine()->getRepository('SpoutletBundle:Rsvp')->findOneBy($criteria);

        if (!$rsvp) {
            throw $this->createNotFoundException();
        }

        return $rsvp;
    }
}

