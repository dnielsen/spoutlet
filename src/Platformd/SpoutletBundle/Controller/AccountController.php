<?php

namespace Platformd\SpoutletBundle\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Platformd\UserBundle\Entity\User;
use Platformd\EventBundle\Service\GroupEventService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Platformd\UserBundle\Form\Type\AccountSettingsType;
use Platformd\UserBundle\Entity\Avatar;
use Platformd\UserBundle\Form\Type\AvatarType;
use Platformd\UserBundle\Exception\ApiRequestException;
use Platformd\UserBundle\Form\Type\UnsubscribeFormType;
use Platformd\SpoutletBundle\Form\Type\TradeshowCompleteRegType;
use Symfony\Component\Form as Form;

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

        $localAuth = $this->container->getParameter('local_auth');

        if (!$localAuth) {

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
                $cevoUserId = $user->getUuid();

                if ($cevoUserId && $cevoUserId > 0) {
                    return $this->redirect(sprintf('http://www.alienwarearena.com/%s/member/%d', $subdomain , $cevoUserId));
                }
            }

            return $this->redirect('http://www.alienwarearena.com/account/profile');
        } else {
            return $this->render('UserBundle:Profile:show.html.twig', array(
                'user' => $user,
            ));
        }
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

    public function videosAction(Request $request)
    {
        $this->checkSecurity();

        $page    = $request->query->get('page', 1);
        $user    = $this->getUser();
        $manager = $this->getYoutubeManager();
        $pager   = $manager->findUserAccountVideos($user, 10, $page);
        $videos  = $pager->getCurrentPageResults();

        return $this->render('SpoutletBundle:Account:videos.html.twig', array(
            'pager'  => $pager,
            'videos' => $videos,
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

    protected function getYoutubeManager()
    {
        return $this->get('platformd.model.youtube_manager');
    }

    public function settingsAction(Request $request)
    {
        $this->checkSecurity();

        $form = $this->createForm($this->getFormType(), $this->getUser());

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                try {
                    $this->getUserManager()->updateApiPassword($form->getData(), $form->get('plainPassword')->getData());
                    $this->setFlash('success', 'platformd.user.account.changes_saved');

                    return $this->redirect($this->generateUrl('accounts_settings'));
                } catch (ApiRequestException $e) {
                    $this->setFlash('error', 'The system is currently unable to process your request. Please try again shortly.');
                }
            }
        }

        $avatarManager = $this->getAvatarManager();
        $data          = $avatarManager->getAvatarListingData($this->getUser(), 184);
        $newAvatar     = new Avatar();

        $newAvatar->setUser($this->getUser());

        $avatarForm         = $this->createForm(new AvatarType(), $newAvatar);
        $subscriptionForm   = $this->createForm($this->getSubscriptionFormType(), $this->getUser());

        return $this->render('SpoutletBundle:Account:settings.html.twig', array(
            'form'              => $form->createView(),
            'avatarForm'        => $avatarForm->createView(),
            'data'              => $data,
            'subscriptionForm'  => $subscriptionForm->createView(),
        ));
    }

    public function subscriptionSettingsAction(Request $request)
    {
        $this->checkSecurity();

        $form = $this->createForm($this->getSubscriptionFormType(), $this->getUser());

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            $this->getUserManager()->updateUser($form->getData());
            $this->setFlash('success', 'platformd.user.account.changes_saved');
        }

        return $this->redirect($this->generateUrl('accounts_settings'));
    }

    public function addAvatarAction(Request $request)
    {
        $this->checkSecurity();

        $newAvatar     = new Avatar();
        $newAvatar->setUser($this->getUser());

        $form = $this->createForm(new AvatarType(), $newAvatar);

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $newAvatar = $form->getData();

                $this->getAvatarManager()->save($newAvatar);

                if ($newAvatar->getUuid()) {
                    return $this->redirect($this->generateUrl('avatar_crop', array(
                        'uuid' => $newAvatar->getUuid(),
                    )));
                }
            }
        }

        $this->setFlash('error', 'platformd.user.avatars.invalid_avatar');

        return $this->redirect($this->generateUrl('accounts_settings'));
    }

    private function getFormType()
    {
        return $this->get('platformd_user.account_settings.form.type');
    }

    private function getSubscriptionFormType()
    {
        return $this->get('platformd_user.subscription_settings.form.type');
    }

    public function incompleteAction(Request $request)
    {
        $this->checkSecurity();

        $user = $this->getCurrentUser();

        if ($this->getUserManager()->isUserAccountComplete($user)) {
            return $this->redirect($this->generateUrl('accounts_settings'));
        }

        $form        = $this->createForm('platformd_incomplete_account', $user);
        $childErrors = false;

        if($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->hasChildren()) {
                foreach ($form->getChildren() as $child) {
                    if (!$child->isValid()) {
                        $childErrors = true;
                    }
                }
            }

            if($form->isValid()) {
                try {
                    $this->getUserManager()->updateUserAndApi($form->getData());
                    return $this->redirect($this->generateUrl('accounts_settings'));
                } catch (ApiRequestException $e) {
                    $this->setFlash('error', 'The system is currently unable to process your request. Please try again shortly.');
                }
            }
        }

        return $this->render('SpoutletBundle:Account:incomplete.html.twig', array(
            'form'        => $form->createView(),
            'errors'      => $form->getErrors(),
            'childErrors' => $childErrors,
        ));
    }

    public function unsubscribeAction(Request $request, $email)
    {
        if ($email) {
            try {
                $user = $this->getUserManager()->findUserByEmail(urldecode($email));

                if ($user) {

                    $form = $this->createForm(new UnsubscribeFormType(), array('unsubscribe' => false, 'email' => $email));

                    if ($request->getMethod() == 'POST') {
                        $form->bindRequest($request);

                        $data = $form->getData();

                        try {
                            if ($data['unsubscribe']) {
                                $user->setSubscribedAlienwareEvents(false);
                                $this->getUserManager()->updateUserAndApi($user);
                            }
                        } catch (\PDOException $e) {
                            # this is for catching the PDOException due to blank usernames
                            return $this->render('SpoutletBundle:Account:unsubscribe.html.twig', array(
                                'userIsValid'   => false,
                                'success'       => false,
                                'apiException'  => true,
                            ));
                        }

                        return $this->render('SpoutletBundle:Account:unsubscribe.html.twig', array(
                            'success'      => true,
                            'userIsValid'  => true,
                        ));
                    }

                    return $this->render('SpoutletBundle:Account:unsubscribe.html.twig', array(
                        'form'          => $form->createView(),
                        'email'         => $email,
                        'userIsValid'   => true,
                        'success'       => false,
                    ));
                }
            } catch (UsernameNotFoundException $e) {
                return $this->render('SpoutletBundle:Account:unsubscribe.html.twig', array(
                    'userIsValid' => false,
                    'success'     => false,
                ));
            }
        }

        return $this->render('SpoutletBundle:Account:unsubscribe.html.twig', array(
            'userIsValid' => false,
            'success'     => false,
        ));
    }

    public function confirmTradeshowRegAction(Request $request, $token)
    {
        $manager    = $this->getUserManager();
        $user       = $manager->findUserByConfirmationToken($token);

        if (null === $user) {
            $this->createNotFoundException('User not found.');
        }

        $default = array('username' => null, 'password' => null);
        $form = $this->createFormBuilder($default)
            ->add('username', 'text', array('required' => true, 'label' => 'Username:'))
            ->add('password', 'password', array('required' => true, 'label' => 'Password:'))
            ->getForm()
        ;

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);
            $data = $form->getData();

            if (!$data['username']) {
                $form->addError(new Form\FormError('Please enter a username.'));
            }

            if (!$data['password']) {
                $form->addError(new Form\FormError('Please enter a password.'));
            }

            if (count($form->getErrors()) > 0) {
                return $this->render('SpoutletBundle:Account:tradeshow_confirm.html.twig', array(
                    'form' => $form->createView(),
                    'token' => $token,
                    'errors' => $form->getErrors(),
                ));
            }

            $usernameExists     = $manager->findUserByUsername($data['username']);
            $passwordTooShort   = strlen($data['password']) < 5;

            if ($usernameExists) {
                $form->addError(new Form\FormError('Username already exists.'));
            }

            if ($passwordTooShort) {
                $form->addError(new Form\FormError('Password must be more than 5 characters.'));
            }

            if (count($form->getErrors()) > 0) {
                return $this->render('SpoutletBundle:Account:tradeshow_confirm.html.twig', array(
                    'form' => $form->createView(),
                    'token' => $token,
                    'errors' => $form->getErrors(),
                ));
            }

            $user->setUsername($data['username']);
            $user->setPlainPassword($data['password']);
            $user->setConfirmationToken(null);
            $user->setEnabled(true);

            $manager->updateUser($user);

            return $this->redirect($this->generateUrl('fos_user_security_login', array('f' => 'reg')));

        }

        return $this->render('SpoutletBundle:Account:tradeshow_confirm.html.twig', array(
            'form' => $form->createView(),
            'token' => $token,
            'errors' => null,
        ));
    }
}
