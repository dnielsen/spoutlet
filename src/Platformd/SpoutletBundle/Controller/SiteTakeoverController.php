<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\SiteTakeover;
use Symfony\Component\HttpFoundation\Request;
use Platformd\SpoutletBundle\Takeover\SiteTakeoverListener;

/**
 * Site Takeover controller.
 *
 */
class SiteTakeoverController extends Controller
{
    public function takeoverAction()
    {
        $site           = $this->getCurrentSite();
        $em             = $this->getDoctrine()->getEntityManager();
        $takeover       = $em->getRepository('SpoutletBundle:SiteTakeover')->getCurrentTakeover($site);
        $session        = $this->get('session');
        $continueUrl    = $session->get(SiteTakeoverListener::TARGET_PATH_KEY);
        $session->remove(SiteTakeoverListener::TARGET_PATH_KEY);

        if (!$continueUrl) {
            $continueUrl = $this->generateUrl('default_index');
        }

        return $this->render('SpoutletBundle:SiteTakeover:takeover.html.twig', array(
            'takeover'      => $takeover,
            'continueUrl'   => $continueUrl,
        ));
    }

    public function takeoverSpecifiedAction($id)
    {
        $site           = $this->getCurrentSite();
        $em             = $this->getDoctrine()->getEntityManager();
        $takeover       = $em->getRepository('SpoutletBundle:SiteTakeover')->find($id);
        $continueUrl    = $this->generateUrl('default_index');

        if (!$takeover) {
            $this->setFlash('error', 'Could not find specified takeover');
            return $this->redirect($continueUrl);
        }

        return $this->render('SpoutletBundle:SiteTakeover:takeover.html.twig', array(
            'takeover'      => $takeover,
            'continueUrl'   => $continueUrl,
        ));
    }
}
