<?php

namespace Platformd\UserBundle\Controller;

use FOS\UserBundle\Controller\ProfileController as BaseProfileController;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
}