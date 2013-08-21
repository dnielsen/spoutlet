<?php

namespace Platformd\SweepstakesBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Platformd\SweepstakesBundle\Entity\Sweepstakes;
use Platformd\SweepstakesBundle\Entity\SweepstakesEntry;
use Platformd\SweepstakesBundle\Entity\SweepstakesAnswer;
use Platformd\SweepstakesBundle\Form\Type\SweepstakesEntryType;

use Platformd\GroupBundle\Entity\GroupMembershipAction;
use Platformd\GroupBundle\Event\GroupEvent;
use Platformd\GroupBundle\GroupEvents;
use Platformd\CEVOBundle\Api\ApiException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class FrontendController extends Controller
{
    public function _sweepstakesFlashMessageAction($slug, $entryId = null)
    {
        $sweepstakes = $this->findSweepstakes($slug, false);

        if ($entryId) {
            $assignedEntry = $this->getEntryRepo()->findOneByIdAndUser($entryId, $this->getUser());
        } else {
            $assignedEntry = null;
        }

        if ($assignedEntry) { # the user has a key, so let's display it for them

            $response = $this->render('SweepstakesBundle:Frontend:_showKey.html.twig', array(
                'sweepstakes' => $sweepstakes
            ));

            $this->varnishCache($response, 60);

            return $response;
        }

        return new Response();
    }

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
    public function showAction($slug, $entryId = null, Request $request)
    {
        $sweepstakes   = $this->findSweepstakes($slug, false);
        $user          = $this->getCurrentUser();
        $isGroupMember = null;
        $em            = $this->getDoctrine()->getEntityManager();

        $canTest = $sweepstakes->getTestOnly() && $this->isGranted(array('ROLE_ADMIN', 'ROLE_SUPER_ADMIN'));

        if ((!$sweepstakes->getPublished() || !$sweepstakes->isCurrentlyOpen()) && !$canTest) {
            throw $this->createNotFoundException();
        }

        if (!$this->isGranted('ROLE_USER')) {
            $isEntered = false;
        } else {
            $isEntered     = (bool) $this->getEntryRepo()->findOneBySweepstakesAndUser($sweepstakes, $user);
            $isGroupMember = $sweepstakes->getGroup() ? $this->getGroupManager()->isMember($user, $sweepstakes->getGroup()) : null;
        }

        $entry = new SweepstakesEntry($sweepstakes);

        foreach ($sweepstakes->getQuestions() as $question) {
            $entry->addAnswer(new SweepstakesAnswer($question, $entry));
        }

        $entryForm = $this->createForm(new SweepstakesEntryType($user), $entry);

        if($request->getMethod() == 'POST') {

            // need to make this more smart - if no user and no registration details, then do something error-ey
            //$this->enforceUserSecurity();

            $existing = $user ? $this->getEntryRepo()->findOneBySweepstakesAndUser($sweepstakes, $user) : null;
            if ($existing) {
                $this->setFlash('error', 'already_entered_sweepstakes');
                return $this->redirectToShow($sweepstakes);
            }

            $entryForm->bindRequest($request);

            if($entryForm->isValid()) {

                $entry = $entryForm->getData();

                if (!$user) {
                    // user object from registration form section, get this to reg form handler somehow
                    $user = $entryForm->get('registrationDetails')->getData();
                }

                $entry->setUser($user);

                $clientIp = $this->getIpLookupUtil()->getClientIp($request);
                $entry->setIpAddress($clientIp);

                $countryCode = $this->getIpLookupUtil()->getCountryCode($clientIp);
                $country = $em->getRepository('SpoutletBundle:Country')->findOneByCode($countryCode);

                if (!$country) {
                    $this->setFlash('error', 'sweepstakes.entry.error.invalid_country');
                    return $this->redirectToShow($sweepstakes);
                }

                $entry->setCountry($country);

                $em->persist($entry);
                $em->flush();

                if($this->getCurrentSite()->getSiteFeatures()->getHasGroups() && $sweepstakes->getGroup()) {
                    $this->getGroupManager()->autoJoinGroup($sweepstakes->getGroup(), $user);
                }

                // arp - enteredsweepstakes
                try {
                    $response = $this->getCEVOApiManager()->GiveUserXp('enteredsweepstakes', $user->getCevoUserId());
                } catch (ApiException $e) {

                }

                return $this->redirect($this->generateUrl('sweepstakes_show', array('slug' => $slug)));
            }
        }

        return array(
            'sweepstakes'       => $sweepstakes,
            'isEntered'         => $isEntered,
            'isGroupMember'     => $isGroupMember,
            'entryId'           => $entryId,
            'entryForm'         => $entryForm->createView(),
        );
    }

    /**
     * @param $slug
     * @param integer $restrictUnpublished
     * @return \Platformd\SweepstakesBundle\Entity\Sweepstakes
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    private function findSweepstakes($slug, $restrictUnpublished = true)
    {
        $sweepstakes = $this->getSweepstakesRepo()->findOneBySlugForSite($slug, $this->getCurrentSite());

        if (!$sweepstakes) {
            throw $this->createNotFoundException('No sweepstakes for slug '.$slug);
        }

        $canTest = $sweepstakes->getTestOnly() && $this->isGranted(array('ROLE_ADMIN', 'ROLE_SUPER_ADMIN'));

        if (($restrictUnpublished && !$sweepstakes->getPublished()) && !$canTest) {
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
            ->getRepository('SweepstakesBundle:SweepstakesEntry')
        ;
    }

    /**
     * @return \Platformd\GroupBundle\Model\GroupManager
     */
    private function getGroupManager()
    {
        return $this->get('platformd.model.group_manager');
    }

    /**
     * @return \Platformd\CEVOBundle\Api\ApiManager
     */
    private function getCEVOApiManager()
    {
        return $this->get('pd.cevo.api.api_manager');
    }
}
