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
    
    public function deleteAction($id)
    {
        $manager = $this->get('fos_user.user_manager');
        
        if (!($user = $manager->findUserBy(array('id' => $id))) || $user->isSuperAdmin()) {
            
            throw $this->createNotFoundException(sprintf('Unable to retrieve user #%d', $id));
        }

        // TODO : Use a confirm page and a DELETE HTTP Method

        $manager->deleteUser($user);

        $this
            ->getRequest()
            ->getSession()
            ->setFlash('notice', sprintf('User "%s" has been successfully deleted', $user->getUsername()));

        return $this->redirect($this->generateUrl('Platformd_UserBundle_admin_index'));
    }
}
