<?php

namespace Platformd\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;

use Platformd\UserBundle\Form\Type\EditUserFormType;

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

    public function editAction($id)
    {
        $manager = $this->get('fos_user.user_manager');
        $translator = $this->get('translator');

        if (!($user = $manager->findUserBy(array('id' => $id))) || $user->isSuperAdmin()) {
            
            throw $this->createNotFoundException(sprintf('Unable to retrieve user #%d', $id));
        }

        $form = $this->createForm(new EditUserFormType(), $user, array('allow_promote' => $this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')));
        $request = $this->getRequest();

        // TODO : use update http method
        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {
                
                $manager->updateUser($user);

                $request
                    ->getSession()
                    ->setFlash('notice', $translator->trans('fos_user_admin_edit_success', array(
                        '%username%' => $user->getUsername()
                    ), 'FOSUserBundle'));

                return $this->redirect($this->generateUrl('Platformd_UserBundle_admin_index'));
            }
        }
        
        return $this->render('UserBundle:Admin:edit.html.twig', array(
            'user' => $user,
            'form' => $form->createView()
        ));
    }
    
    public function deleteAction($id)
    {
        $manager = $this->get('fos_user.user_manager');
        $translator = $this->get('translator');
        
        if (!($user = $manager->findUserBy(array('id' => $id))) || $user->isSuperAdmin()) {
            
            throw $this->createNotFoundException(sprintf('Unable to retrieve user #%d', $id));
        }

        // TODO : Use a confirm page and a DELETE HTTP Method

        $manager->deleteUser($user);

        $this
            ->getRequest()
            ->getSession()
            ->setFlash('notice', $translator->trans('fos_user_admin_delete_success', array(
                '%username' => $user->getUsername() 
            ), 'FOSUserBundle'));
            
        return $this->redirect($this->generateUrl('Platformd_UserBundle_admin_index'));
    }

    public function approveAvatarAction($id) 
    {
        $manager = $this->get('fos_user.user_manager');

        if (!$user = $manager->findUserBy(array('id' => $id))) {
            
            throw $this->createNotFoundException();
        }

        $user->approveAvatar();
        $manager->updateUser($user);

        return $this->redirect($this->generateUrl('Platformd_UserBundle_admin_index'));
    }
}
