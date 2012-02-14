<?php

namespace Platformd\SweepstakesBundle\Controller;

use Platformd\SweepstakesBundle\Entity\Sweepstakes;
use Symfony\Component\HttpFoundation\Request;
use Platformd\SpoutletBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class FrontendController extends Controller
{
    public function indexAction()
    {
        $sweepstakess = $this->getSweepstakesRepo()->findPublished($this->getLocale());

    	return $this->render('SweepstakesBundle:Frontend:index.html.twig', array(
            'sweepstakess' => $sweepstakess
        ));
    }

    /**
     * @Template
     * @param integer $entryId The optiona entrance id that was just assigned
     * @param $slug
     * @return array
     */
    public function showAction($slug, $entryId = null)
    {
        $sweepstakes = $this->getSweepstakesRepo()->findOnePublishedBySlug($slug, $this->getLocale());

        if (!$sweepstakes) {
            throw $this->createNotFoundException('No sweepstakes for slug '.$slug);
        }

        if ($entryId) {
            $assignedEntry = $this->getKeyRepository()->findOneByIdAndUser($keyId, $this->getUser());
        } else {
            $assignedEntry = null;
        }

        return array(
            'sweepstakes' => $sweepstakes,
            'assignedEntry' => $assignedEntry,
        );
    }
}
