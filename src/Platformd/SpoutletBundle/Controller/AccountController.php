<?php

namespace Platformd\SpoutletBundle\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Platformd\UserBundle\Entity\User;
use Platformd\EventBundle\Service\GroupEventService;
use Symfony\Component\HttpFoundation\Request;

class AccountController extends Controller
{
	public function profileAction($username = null)
    {
        $context = $this->get('security.context');

        if ($username) {
            $manager = $this->get('fos_user.user_manager');
            if (!$user = $manager->findUserByUsername($username)) {
                throw $this->createNotFoundException(sprintf('Unable to find an user with username "%s"', $username));
            }
        } else if ($context->isGranted('IS_AUTHENTICATED_FULLY')) {
            $user = $this->get('security.context')->getToken()->getUser();
        } else {
            throw $this->createNotFoundException();
        }

        $locale = $this->getLocale();

        switch ($locale) {
            case 'ja':
                $subdomain = '/japan';
                break;

            case 'zh':
                $subdomain = '/china';
                break;

            case 'es':
                $subdomain = '/latam';
                break;

            default:
                $subdomain = '';
                break;
        }

        if ($user) {
            $cevoUserId = $user->getCevoUserId();

            if ($cevoUserId && $cevoUserId > 0) {
                return $this->redirect(sprintf('http://www.alienwarearena.com/%s/member/%d', $subdomain , $cevoUserId));
            }
        }

        return $this->redirect('http://www.alienwarearena.com/account/profile');
	}


	public function accountAction()
	{
        $this->checkSecurity();

        // we have a little, temporary message until around April 1st
        $isJapan = $this->getLocale() == 'ja';
        // this is April 1st at midnight EST - not sure what the EXACT time should be
        $isItAprilYet = time() < 1333256400;
        $showSweepstakesMessage = ($isJapan && $isItAprilYet);



		return $this->render('SpoutletBundle:Account:account.html.twig', array(
            'showSweepstakesMessage' => $showSweepstakesMessage,
            'cevoUserId' => $this->getUser()->getCevoUserId(),
        ));
	}

    public function eventsAction()
    {
        $this->checkSecurity();

        /** @var $groupEventService GroupEventService */
        $groupEventService = $this->get('platformd_event.service.group_event');
        $globalEventService = $this->get('platformd_event.service.global_event');

        $upcomingGroupEvents    = $groupEventService->findUpcomingEventsForUser($this->getUser());
        $ownedGroupEvents       = $groupEventService->findUpcomingEventsForUser($this->getUser(), true);
        $pastGroupEvents        = $groupEventService->findPastEventsForUser($this->getUser());
        $pastOwnedGroupEvents   = $groupEventService->findPastEventsForUser($this->getUser(), true);

        $upcomingGlobalEvents   = $globalEventService->findUpcomingEventsForUser($this->getUser());
        $ownedGlobalEvents      = $globalEventService->findUpcomingEventsForUser($this->getUser(), true);
        $pastGlobalEvents       = $globalEventService->findPastEventsForUser($this->getUser());
        $pastOwnedGlobalEvents  = $globalEventService->findPastEventsForUser($this->getUser(), true);

        $upcomingEvents     = array_merge($upcomingGlobalEvents, $upcomingGroupEvents);
        $ownedEvents        = array_merge($ownedGroupEvents, $ownedGlobalEvents);
        $pastEvents         = array_merge($pastGroupEvents, $pastGlobalEvents);
        $pastOwnedEvents    = array_merge($pastOwnedGroupEvents, $pastOwnedGlobalEvents);

        uasort($upcomingEvents, array($this, 'eventCompare'));
        uasort($ownedEvents, array($this, 'eventCompare'));
        uasort($pastEvents, array($this, 'eventCompare'));
        uasort($pastOwnedEvents, array($this, 'eventCompare'));

        return $this->render('SpoutletBundle:Account:events.html.twig', array(
            'events'            => $upcomingEvents,
            'ownedEvents'       => $ownedEvents,
            'pastEvents'        => $pastEvents,
            'pastOwnedEvents'   => $pastOwnedEvents,
        ));
    }

    private function eventCompare($a, $b) {

        if ($a->getStartsAt() == $b->getStartsAt()) {
            return 0;
        }
        return ($a->getStartsAt() < $b->getStartsAt()) ? -1 : 1;

    }

    public function videosAction()
    {
        return $this->redirect('/video/edit');
    }

    /**
     * Displays a list of giveaway keys that this user has earned
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function giveawaysAction()
    {
        $this->checkSecurity();

        $keyRequests = $this->container
            ->get('pd_giveaway.giveaway_manager')
            ->getGiveawayKeyRequestsForUser($this->getUser())
        ;

        return $this->render('SpoutletBundle:Account:giveaways.html.twig', array(
            'keyRequests' => $keyRequests,
        ));
    }

    /**
     * Displays a list of deals that this user has redeemed
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dealsAction()
    {
        $this->checkSecurity();

        $em = $this->getDoctrine()->getEntityManager();

        $dealCodes = $em->getRepository('GiveawayBundle:DealCode')
            ->getUserAssignedCodes($this->getUser())
        ;

        return $this->render('SpoutletBundle:Account:deals.html.twig', array(
            'dealCodes' => $dealCodes,
        ));
    }

    public function groupsAction(Request $request)
    {
        $this->checkSecurity();

        $em = $this->getDoctrine()->getEntityManager();

        $groups = $em->getRepository('GroupBundle:Group')->getAllGroupsForUserAndSite($this->getUser(), $this->getCurrentSite());

        $action = null;

        if ($then = $request->query->get('then')) {
            $action = $then;
        }

        return $this->render('SpoutletBundle:Account:groups.html.twig', array(
            'groups' => $groups,
            'action' => $action,
        ));
    }

    public function photosAction($filter='all')
    {
        $this->checkSecurity();

        $em = $this->getDoctrine()->getEntityManager();

        $galleries = $em->getRepository('SpoutletBundle:Gallery')
                ->findAllGalleriesByCategoryForSite($this->getCurrentSite())
            ;

        switch($filter)
        {
            case 'all':
                $images = $em->getRepository('SpoutletBundle:GalleryMedia')
                    ->findAllPublishedByUserNewestFirst($this->getUser())
                ;
                break;
            case 'deleted':
                $images = $em->getRepository('SpoutletBundle:GalleryMedia')
                    ->findAllDeletedMediaForUser($this->getUser())
                ;
                break;
            default:
                foreach ($galleries as $gallery)
                {
                    if(strtolower($gallery->getName()) == strtolower($filter))
                    {
                        $images = $em->getRepository('SpoutletBundle:GalleryMedia')
                            ->findAllMediaByUserAndGallery($this->getUser(), $gallery)
                        ;
                    }
                }

                if(!isset($images)) {
                    $images = null;
                }
                break;
        }

        return $this->render('SpoutletBundle:Account:photos.html.twig', array(
            'images'    => $images,
        ));
    }

    protected function checkSecurity()
    {
        if (!$this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @return \Platformd\GiveawayBundle\Entity\Repository\GiveawayKeyRepository
     */
    protected function getGiveawayKeyRepository()
    {
        return $this->getDoctrine()
            ->getRepository('GiveawayBundle:GiveawayKey')
        ;
    }
}
