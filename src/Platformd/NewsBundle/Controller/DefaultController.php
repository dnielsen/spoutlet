<?php

namespace Platformd\NewsBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
* 
*/
class DefaultController extends Controller
{
    
    public function indexAction(Request $request)
    {
        $news = $this->getNewsRepo()->findAllForLocale($this->getLocale());

        // allows us to preview how this feature would really work
        $template = 'index.html.twig';

        return $this->render('NewsBundle:Default:'.$template, array(
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

        if (!$news) {
            $this->createNotFoundException('No news for slug '.$slug);
        }

        if ($this->isExternalLink($news)) {
            return $this->createLinkableResponse($news);
        }

        return $this->render('NewsBundle:Default:show.html.twig', array(
            'news' => $news,
        ));
    }
}