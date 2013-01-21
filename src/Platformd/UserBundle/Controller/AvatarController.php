<?php

namespace Platformd\UserBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class AvatarController extends Controller
{
    public function indexAction(Request $request)
    {
        $avatars = $this->get('fos_user.user_manager')->getUserAvatars($user);

        return $this->render('FOSUserBundle:Avatar:index.html.twig', array(
            'avatars' => $avatars
        ));
    }
}
