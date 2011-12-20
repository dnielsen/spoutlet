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
            ->findAllForLocale($this->getLocale())
        ;

        return $this->render('NewsBundle:Default:index.html.twig', array(
            'news' => $news,
        ));
    }

    public function showAction($slug)
    {
        $news = $this->getNewsRepo()
            ->findOneBy(array('slug' => $slug))
        ;

        return $this->render('NewsBundle:Default:show.html.twig', array(
            'news' => $news,
        ));
    }
}