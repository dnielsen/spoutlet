<?php

namespace Platformd\SpoutletBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AccountController extends Controller
{
	public function profileAction($username)
	{
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

	public function changePasswordAction()
	{
		return $this->render('SpoutletBundle:Account:changePassword.html.twig');
	}
}
