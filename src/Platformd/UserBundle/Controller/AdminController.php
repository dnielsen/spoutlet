<?php

namespace Platformd\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;

class AdminController extends Controller
{
    
    public function indexAction() 
    {
        $manager = $this->get('fos_user.user_manager');
        $query = $manager->getFindUserQuery();
        
        $pager = new PagerFanta(new DoctrineORMAdapter($query));
        $pager->setCurrentPage($this->getRequest()->get('page', 1));
        
    	return $this->render('UserBundle:Admin:index.html.twig', array(
            'pager' => $pager
        ));
    }

}
