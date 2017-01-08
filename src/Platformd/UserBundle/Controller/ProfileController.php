<?php

namespace Platformd\UserBundle\Controller;

use FOS\UserBundle\Controller\ProfileController as BaseProfileController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Overrides the FOS profile controller
 */
class ProfileController extends BaseProfileController
{
   public function showAction($username = null)
   {
       $authChecker = $this->container->get('security.authorization_checker');

       if ($username) {
           $manager = $this->container->get('fos_user.user_manager');
           if (!$user = $manager->findUserByUsername($username)) {
               throw new NotFoundHttpException(sprintf('Unable to find an user with username "%s"', $username));
           }
           $isCurrentUser = false;
       } else if ($authChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
           $isCurrentUser = true;
           $user = $this->container->get('security.token_storage')->getToken()->getUser();
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
