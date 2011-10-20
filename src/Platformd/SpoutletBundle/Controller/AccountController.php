<?php

namespace Platformd\SpoutletBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

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

		return $this->render('FOSUserBundle:Profile:show.html.twig', array('user' => $user));		
	}


	public function accountAction()
	{
		return $this->render('SpoutletBundle:Account:account.html.twig');
	}

    public function eventsAction()
    {
        return $this->render('SpoutletBundle:Account:events.html.twig');
    }
}
