<?php

namespace Platformd\SpoutletBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AccountController extends Controller
{
	public function profileAction($username = null)
	{
        if ($username === null) {
            // TODO - get user from security context
            $user = '';
        } else {
            // TODO get user by searching the db, forward 404 if not found
        }

		return $this->render('SpoutletBundle:Account:profile.html.twig');		
	}

	public function editProfileAction()
	{
		return $this->render('SpoutletBundle:Account:profile.html.twig');
	}

	public function accountAction()
	{
		return $this->render('SpoutletBundle:Account:account.html.twig');
	}
}
