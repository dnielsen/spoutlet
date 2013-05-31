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

       /** @var $user \Platformd\UserBundle\Entity\User */

       /** @var $cevoAuthManager \Platformd\CEVOBundle\CevoAuthManager */
       $cevoAuthManager = $this->container->get('pd.cevo.cevo_auth_manager');
       $cevoProfileUrl = $cevoAuthManager->generateCevoUrl('/member/'.$user->getCevoUserId());

       //return new RedirectResponse($cevoProfileUrl);

       return $this->container->get('templating')
           ->renderResponse('FOSUserBundle:Profile:show.html.twig', array(
           'user' => $user,
           'isCurrentUser' => $isCurrentUser,
       ));
   }
}
