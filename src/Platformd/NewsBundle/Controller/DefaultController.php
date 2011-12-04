<?php

namespace Platformd\NewsBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;

/**
* 
*/
class DefaultController extends Controller
{
    
    public function indexAction()
    {
        $news = $this->getNewsRepo()
            ->findAll()
        ;

        return $this->render('NewsBundle:Default:index.html.twig', array(
            'news' => $news,
        ));
    }
}