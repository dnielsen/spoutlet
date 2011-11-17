<?php

namespace Platformd\NewsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;

class AdminController extends Controller
{
    
    public function indexAction()
    {
        $manager = $this
            ->getDoctrine()
            ->getEntityManager()
            ->getRepository('NewsBundle:News');
        $query = $manager->getFindNewsQuery();
        
        $pager = new PagerFanta(new DoctrineORMAdapter($query));
        $pager->setCurrentPage($this->getRequest()->get('page', 1));
        
        return $this->render('NewsBundle:Admin:index.html.twig', array(
            'pager' => $pager
        ));
    }
}
