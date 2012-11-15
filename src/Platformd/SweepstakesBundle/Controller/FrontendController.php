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
        $sweepstakess = $this->getSweepstakesRepo()->findPublished($this->getCurrentSite());

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
        $sweepstakes = $this->findSweepstakes($slug, false);

        if ($entryId) {
            $assignedEntry = $this->getEntryRepo()->findOneByIdAndUser($entryId, $this->getUser());
        } else {
            $assignedEntry = null;
        }

        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $isEntered = false;
        } else {
            $isEntered = (bool) $this->getEntryRepo()->findOneBySweepstakesAndUser($sweepstakes, $this->getUser());
        }

        return array(
            'sweepstakes' => $sweepstakes,
            'assignedEntry' => $assignedEntry,
            'isEntered' => $isEntered,
            'countries' => $sweepstakes->getRuleset()->getAllowedCountries(),
            'ageRestrictions' => $sweepstakes->getRuleset()->areThereAgeRestrictions(),
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
        $sweepstakes = $this->findSweepstakes($slug, false);

        // if we're not even published yet, definitely don't let them!
        if (!$sweepstakes->getPublished()) {
            $this->setFlash('error', 'sweeps_not_open');

            return $this->redirectToShow($sweepstakes);
        }

        // todo, check terms checkboxes
        if (!$request->get('_terms')) {
            $this->setFlash('error', 'sweeps_check_both_boxes');

            return $this->redirectToShow($sweepstakes);
        }

        // only let someone register once
        $existing = $this->getEntryRepo()->findOneBySweepstakesAndUser($sweepstakes, $this->getUser());
        if ($existing) {
            $this->setFlash('error', 'already_entered_sweepstakes');

            return $this->redirectToShow($sweepstakes);
        }

        // check that they pass the new style age-country restriction ruleset
        $countryRepo    = $this->getDoctrine()->getEntityManager()->getRepository('SpoutletBundle:Country');

        $user           = $this->getUser();
        $country        = $countryRepo->findOneByCode($user->getCountry());

        if ($sweepstakes->getRuleset()->doesUserPassRules($user, $country)) {
            $this->setFlash('error', 'not_eligible_sweepstakes');
            return $this->redirectToShow($sweepstakes);
        }

        // make sure that the sweepstakes is active right now
        if (!$sweepstakes->isCurrentlyOpen()) {
            $this->setFlash('error', 'sweeps_not_open');

            return $this->redirectToShow($sweepstakes);
        }

        $entry = $this->getSweepstakesRepo()->createNewEntry(
            $sweepstakes,
            $this->getUser(),
            $request->getClientIp(true)
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
     * @param integer $restrictUnpublished
     * @return \Platformd\SweepstakesBundle\Entity\Sweepstakes
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    private function findSweepstakes($slug, $restrictUnpublished = true)
    {
        $sweepstakes = $this->getSweepstakesRepo()->findOneBySlug($slug, $this->getCurrentSite());

        if (!$sweepstakes) {
            throw $this->createNotFoundException('No sweepstakes for slug '.$slug);
        }

        if ($restrictUnpublished && !$sweepstakes->getPublished()) {
            throw $this->createNotFoundException('But this sweepstakes is not published! '.$slug);
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
