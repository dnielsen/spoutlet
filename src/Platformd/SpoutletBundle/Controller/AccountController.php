<?php

namespace Platformd\SpoutletBundle\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Platformd\UserBundle\Entity\User;
use Platformd\EventBundle\Service\GroupEventService;

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

        if (in_array($locale, array('zh', 'ja'))) {

            if ($user) {
                $cevoUserId = $user->getCevoUserId();

                if ($cevoUserId && $cevoUserId > 0) {
                    return $this->redirect(sprintf('http://www.alienwarearena.com/%s/member/%d', $locale == "ja" ? "japan" : "china" , $cevoUserId));
                }
            }

            return $this->redirect('http://www.alienwarearena.com/account/profile');
        }

		return $this->render('FOSUserBundle:Profile:show.html.twig', array('user' => $user));
	}


	public function accountAction()
	{
        $this->checkSecurity();

        // we have a little, temporary message until around April 1st
        $isJapan = $this->getRequest()->getSession()->getLocale() == 'ja';
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

        $events         = $groupEventService->findUpcomingEventsForUser($this->getUser());
        $ownedEvents    = $groupEventService->findUpcomingEventsForUser($this->getUser(), true);
        $pastEvents     = $groupEventService->findPastEventsForUser($this->getUser());

        return $this->render('SpoutletBundle:Account:events.html.twig', array(
            'events'        => $events,
            'ownedEvents'   => $ownedEvents,
            'pastEvents'    => $pastEvents,
        ));
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

        $dealCodes = $em->getRepository('SpoutletBundle:DealCode')
            ->getUserAssignedCodes($this->getUser())
        ;

        return $this->render('SpoutletBundle:Account:deals.html.twig', array(
            'dealCodes' => $dealCodes,
        ));
    }

    public function groupsAction()
    {
        $this->checkSecurity();

        $em = $this->getDoctrine()->getEntityManager();

        $groups = $em->getRepository('SpoutletBundle:Group')->getAllGroupsForUser($this->getUser());

        return $this->render('SpoutletBundle:Account:groups.html.twig', array(
            'groups' => $groups,
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
