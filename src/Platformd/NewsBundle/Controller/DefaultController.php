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
        $news = $this->getNewsRepo()->findAllForLocale($this->getLocale());

        return $this->render('NewsBundle:Default:index.html.twig', array(
            'news' => $news,
        ));
    }

    public function showAction($slug)
    {
        /*
         * Notice that this does *not* respect "published". This is on purpose,
         * because the client wants to be able to preview news to the client
         */
        $news = $this->getNewsRepo()->findOneBy(array('slug' => $slug));

        return $this->render('NewsBundle:Default:show.html.twig', array(
            'news' => $news,
        ));
    }
}