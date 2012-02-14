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
        $sweepstakes = $this->findSweepstakes($slug);

        if ($entryId) {
            $assignedEntry = $this->getEntryRepo()->findOneByIdAndUser($entryId, $this->getUser());
        } else {
            $assignedEntry = null;
        }

        $isEntered = (bool) $this->getEntryRepo()->findOneBySweepstakesAndUser($sweepstakes, $this->getUser());

        return array(
            'sweepstakes' => $sweepstakes,
            'assignedEntry' => $assignedEntry,
            'isEntered' => $isEntered,
        );
    }

    /**
     * The actual action to enter a sweepstakes
     *
     * @param string $slug
     */
    public function enterAction($slug, Request $request)
    {
        $this->enforceUserSecurity();
        $sweepstakes = $this->findSweepstakes($slug);

        // todo, check terms checkboxes
        if (!$request->get('_terms') || !$request->get('_release')) {
            $this->setFlash('error', 'sweeps_check_both_boxes');

            return $this->redirectToShow($sweepstakes);
        }

        $existing = $this->getEntryRepo()->findOneBySweepstakesAndUser($sweepstakes, $this->getUser());
        if ($existing) {
            $this->setFlash('error', 'already_entered_sweepstakes');

            return $this->redirectToShow($sweepstakes);
        }

        if (!$sweepstakes->isUserOldEnough($this->getUser()->getBirthdate())) {
            $this->setFlash('error', 'not_eligible_sweepstakes');
        }

        if ($sweepstakes->isCountryAllowed($this->getUser()->getCountry())) {
            $this->setFlash('error', 'not_eligible_sweepstakes');
        }

        $entry = $this->getSweepstakesRepo()->createNewEntry(
            $sweepstakes,
            $this->getUser(),
            $request->getClientIp()
        );
        $this->getDoctrine()->getEntityManager()->persist($entry);
        $this->getDoctrine()->getEntityManager()->flush();

        return $this->redirect($this->generateUrl(
            'sweepstakes_show',
            array('slug' => $sweepstakes->getSlug(), 'entryId' => $entry->getId())
        ));
    }

    /**
     * @param $slug
     * @return \Platformd\SweepstakesBundle\Entity\Sweepstakes
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    private function findSweepstakes($slug)
    {
        $sweepstakes = $this->getSweepstakesRepo()->findOnePublishedBySlug($slug, $this->getLocale());

        if (!$sweepstakes) {
            throw $this->createNotFoundException('No sweepstakes for slug '.$slug);
        }

        return $sweepstakes;
    }

    private function redirectToShow(Sweepstakes $sweepstakes)
    {
        return $this->redirect($this->generateUrl(
            'sweepstakes_show',
            array('slug' => $sweepstakes->getSlug())
        ));
    }

    /**
     * @return \Platformd\SweepstakesBundle\Entity\EntryRepository
     */
    private function getEntryRepo()
    {
        return $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('SweepstakesBundle:Entry')
        ;
    }
}
