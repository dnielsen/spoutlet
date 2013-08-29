<?php

namespace Platformd\SweepstakesBundle\Controller;

use Platformd\SweepstakesBundle\Entity\Sweepstakes;
use Symfony\Component\HttpFoundation\Request;
use Platformd\SpoutletBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Platformd\GroupBundle\Entity\GroupMembershipAction;
use Platformd\GroupBundle\Event\GroupEvent;
use Platformd\GroupBundle\GroupEvents;
use Platformd\CEVOBundle\Api\ApiException;
use Symfony\Component\HttpFoundation\Response;
use Platformd\UserBundle\Entity\RegistrationSource;

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
    public function showAction($slug, $entryId = null)
    {
        $sweepstakes = $this->findSweepstakes($slug, false);

        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $isEntered = false;
        } else {
            $isEntered = (bool) $this->getEntryRepo()->findOneBySweepstakesAndUser($sweepstakes, $this->getUser());
        }

        $permalink = $this->get('platformd.model.comment_manager')->checkThread($sweepstakes);

        return array(
            'sweepstakes'   => $sweepstakes,
            'isEntered'     => $isEntered,
            'groupManager'  => $this->getGroupManager(),
            'permalink'     => $permalink,
            'entryId'       => $entryId,
            'regSourceType' => RegistrationSource::REGISTRATION_SOURCE_TYPE_SWEEPSTAKES,
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
        $canTest = $sweepstakes->getTestOnly() && $this->isGranted(array('ROLE_ADMIN', 'ROLE_SUPER_ADMIN'));
        if (!$sweepstakes->getPublished() && !$canTest) {
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
        $user = $this->getUser();

        if ($sweepstakes->getRuleset() && !$sweepstakes->getRuleset()->doesUserPassRules($user, $this->getCurrentCountry())) {
            $this->setFlash('error', 'not_eligible_sweepstakes');
            return $this->redirectToShow($sweepstakes);
        }

        // make sure that the sweepstakes is active right now
        if (!$sweepstakes->isCurrentlyOpen()) {
            $this->setFlash('error', 'sweeps_not_open');

            return $this->redirectToShow($sweepstakes);
        }

        $joinGroup = $request->get('join_checkbox');

        # if user has elected to join the group associated with this deal, we add them to the list of members
        if($joinGroup && $this->getCurrentSite()->getSiteFeatures()->getHasGroups()) {
            if($sweepstakes->getGroup()) {
                $groupManager = $this->getGroupManager();
                $group = $sweepstakes->getGroup();

                if ($groupManager->isAllowedTo($user, $group, $this->getCurrentSite(), 'JoinGroup')) {
                    // TODO This should probably be refactored to use the global activity table
                    $joinAction = new GroupMembershipAction();
                    $joinAction->setGroup($group);
                    $joinAction->setUser($user);
                    $joinAction->setAction(GroupMembershipAction::ACTION_JOINED);

                    $group->getMembers()->add($user);
                    $group->getUserMembershipActions()->add($joinAction);

                    // TODO Add a service layer for managing groups and dispatching such events
                    /** @var \Symfony\Component\EventDispatcher\EventDispatcher $dispatcher */
                    $dispatcher = $this->get('event_dispatcher');
                    $event = new GroupEvent($group, $user);
                    $dispatcher->dispatch(GroupEvents::GROUP_JOIN, $event);

                    $groupManager->saveGroup($group);

                    if($group->getIsPublic()) {
                        try {
                            $response = $this->getCEVOApiManager()->GiveUserXp('joingroup', $user->getCevoUserId());
                        } catch (ApiException $e) {

                        }
                    }
                }
            }
        }

        $entry = $this->getSweepstakesRepo()->createNewEntry(
            $sweepstakes,
            $this->getUser(),
            $this->getClientIp($request)
        );
        $this->getDoctrine()->getEntityManager()->persist($entry);
        $this->getDoctrine()->getEntityManager()->flush();

        // arp - enteredsweepstakes
        try {
            $response = $this->getCEVOApiManager()->GiveUserXp('enteredsweepstakes', $user->getCevoUserId());
        } catch (ApiException $e) {

        }

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
        $sweepstakes = $this->getSweepstakesRepo()->findOneBySlugWithoutPublished($slug, $this->getCurrentSite());

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
            ->getRepository('SweepstakesBundle:Entry')
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
