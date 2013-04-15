<?php

namespace Platformd\SpoutletBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


/**
 * Admin controller for events
 */
class AdminController extends Controller
{
    /**
     * Admin homepage
     *
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function indexAction()
    {
        return $this->render('SpoutletBundle:Admin:index.html.twig');
    }
}
