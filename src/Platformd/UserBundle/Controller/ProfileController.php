<?php

namespace Platformd\UserBundle\Controller;

use FOS\UserBundle\Controller\ProfileController as BaseProfileController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Overrides the FOS profile controller
 */
class ProfileController extends BaseProfileController
{
    public function editAction()
    {
        // temporarily disable this page until we figure out how it relates to CEVO
        return $this->redirect($this->generateUrl('default_index'));
        $response = parent::editAction();

        // if we're redirecting (i.e. success and the user's avatar is marked as changed,
        // then user has a new avatar that must need approving
        $form = $this->container->get('fos_user.profile.form');
        $user = $form->getData()->user;

        if ($response instanceof RedirectResponse && $user->isAvatarChanged()) {
            // just do a sanity check, make sure they *do* need approval

            if (!$user->isAvatarApproved()) {

                $this->container
                    ->get('session')
                    ->setFlash('success', 'avatar_awaiting_approval')
                ;
            }
        }

        return $response;
    }

   public function showAction($username = null)
   {
       $context = $this->container->get('security.context');

       if ($username) {
           $manager = $this->container->get('fos_user.user_manager');
           if (!$user = $manager->findUserByUsername($username)) {
               throw new NotFoundHttpException(sprintf('Unable to find an user with username "%s"', $username));
           }
           $isCurrentUser = false;
       } else if ($context->isGranted('IS_AUTHENTICATED_FULLY')) {
           $isCurrentUser = true;
           $user = $this->container->get('security.context')->getToken()->getUser();
       } else {
           throw new NotFoundHttpException();
       }

       return $this->container->get('templating')
           ->renderResponse('FOSUserBundle:Profile:show.html.twig', array(
           'user' => $user,
           'isCurrentUser' => $isCurrentUser,
       ));
   }
}