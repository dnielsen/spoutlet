<?php

namespace Platformd\HPCloudBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class DefaultController extends Controller
{
    
    public function indexAction($name)
    {
        return $this->render('HPCloudBundle:Default:index.html.twig', array('name' => $name));
    }
}
