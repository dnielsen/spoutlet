<?php

namespace Platformd\SpoutletBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AccountController extends Controller
{
	public function profileAction($username)
	{
        $manager = $this->get('fos_user.user_manager');
        if (!$user = $manager->findUserByUsername($username)) {
            
            throw $this->createNotFoundException(sprintf('Unable to find an user with username "%s"', $username));
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
