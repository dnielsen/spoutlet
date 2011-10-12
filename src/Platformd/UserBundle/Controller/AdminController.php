<?php

namespace Platformd\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AdminController extends Controller
{
    
    public function indexAction() 
    {

    	return $this->render('UserBundle:Admin:index.html.twig');
    }

}
