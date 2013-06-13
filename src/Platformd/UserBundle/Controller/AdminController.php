<?php

namespace Platformd\UserBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;

use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;

use Platformd\UserBundle\Form\Type\EditUserFormType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Admin controller for users
 */
class AdminController extends Controller
{
    public function indexAction(Request $request)
    {
        $this->addUserBreadcrumb();
        $manager = $this->get('fos_user.user_manager');

        $search = $request->get('search');
        $type   = $request->get('type');

        $query  = $manager->getFindUserQuery($search, $type);

        $pager = new PagerFanta(new DoctrineORMAdapter($query));
        $pager->setCurrentPage($this->getRequest()->get('page', 1));

        return $this->render('UserBundle:Admin:index.html.twig', array(
            'pager' => $pager,
            'search' => $search,
        ));
    }

    public function showAction($id)
    {
        $this->addUserBreadcrumb()->addChild('Details');
        $manager = $this->get('fos_user.user_manager');

        if (!$user = $manager->findUserBy(array('id' => $id))) {
            throw $this->createNotFoundException(sprintf('Unable to retrieve user #%d', $id));
        }

        return $this->render('UserBundle:Admin:show.html.twig', array(
            'user' => $user,
        ));
    }

    public function editAction($id)
    {
        $this->addUserBreadcrumb()->addChild('Edit');
        $manager = $this->get('fos_user.user_manager');

        if (!$user = $manager->findUserBy(array('id' => $id))) {
            throw $this->createNotFoundException(sprintf('Unable to retrieve user #%d', $id));
        }

        $form = $this->createForm(new EditUserFormType(), $user, array(
            'allow_promote' => $this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'),
            'local_auth' => $this->container->getParameter('local_auth'),
        ));

        return $this->render('UserBundle:Admin:edit.html.twig', array(
            'user' => $user,
            'form' => $form->createView()
        ));
    }

    public function updateAction(Request $request, $id)
    {
        $this->addUserBreadcrumb()->addChild('Update');
        $manager = $this->get('fos_user.user_manager');
        $translator = $this->get('translator');

        if (!$user = $manager->findUserBy(array('id' => $id))) {
            throw $this->createNotFoundException(sprintf('Unable to retrieve user #%d', $id));
        }
        $form = $this->createForm(new EditUserFormType(), $user, array(
            'allow_promote' => $this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'),
            'local_auth' => $this->container->getParameter('local_auth'),
        ));

        $form->bindRequest($request);
        if ($form->isValid()) {
            $manager->updateUser($user);

            $request
                ->getSession()
                ->setFlash('success', $translator->trans('fos_user_admin_edit_success', array(
                    '%username%' => $user->getUsername()
                ), 'FOSUserBundle'));

            return $this->redirect($this->generateUrl('Platformd_UserBundle_admin_index'));
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
            ->setFlash('success', $translator->trans('fos_user_admin_delete_success', array(
                '%username' => $user->getUsername()
            ), 'FOSUserBundle'));

        return $this->redirect($this->generateUrl('Platformd_UserBundle_admin_index'));
    }

    public function resetPasswordAction(Request $request, $id)
    {
        $manager = $this->get('fos_user.user_manager');
        $translator = $this->get('translator');

        if (!$user = $manager->findUserBy(array('id' => $id))) {

            throw $this->createNotFoundException();
        }

        $manager->setNewPassword($user);
        $manager->updateUser($user);

        $this->get('fos_user.mailer')->sendResettedPasswordMessage($user);

        $request
            ->getSession()
            ->setFlash('success', $translator->trans('fos_user_admin_resetted_password_success', array(
                '%email%' => $user->getEmail()
            ), 'FOSUserBundle'))
        ;

        return $this->redirect($this->generateUrl('Platformd_UserBundle_admin_index'));
    }

    public function unapprovedAvatarsAction(Request $request)
    {
        if ($request->getMethod() == 'POST') {
            $ids = $request->request->get('selected', array());

            if (count($ids) == 0) {
                $this->setFlash('error', 'platformd.admin.avatars.unapproved.no_avatars_selected');
                $this->redirect($this->generateUrl('admin_unapproved_avatars'));
            }

            $processType = $request->request->get('process_type', null);

            if ($processType != 'approve' && $processType != 'reject') {
                $this->setFlash('error', 'platformd.admin.avatars.unapproved.form_error');
                $this->redirect($this->generateUrl('admin_unapproved_avatars'));
            }

            $selectedIds = array();

            foreach ($ids as $avatarId) {
                if ($avatarId != '') {
                    $selectedIds[] = $avatarId;
                }
            }

            if (count($selectedIds) == 0) {
                $this->setFlash('error', 'platformd.admin.avatars.unapproved.no_avatars_selected');
                $this->redirect($this->generateUrl('admin_unapproved_avatars'));
            }

            $this->getAvatarManager()->processAvatars($selectedIds, $processType);

            $flash = $this->trans('platformd.admin.avatars.unapproved.process_success', array(
                '%count%' => count($selectedIds),
                '%processType%' => $this->trans('platformd.admin.avatars.unapproved.flash_type_'.$processType),
            ));

            $this->setFlash('success', $flash);
            $this->redirect($this->generateUrl('admin_unapproved_avatars'));
        }

        $page    = $request->query->get('page', 1);
        $avatars = $this->getAvatarManager()->getUnapprovedAvatars(64, $page, $pager);

        return $this->render('UserBundle:Admin:unapprovedAvatars.html.twig', array(
            'avatars' => $avatars,
            'pager'   => $pager,
        ));
    }

    public function loginsAction($id)
    {
        $this->addUserBreadcrumb()->addChild('User Logins');

        $manager = $this->get('fos_user.user_manager');

        if (!$user = $manager->findUserBy(array('id' => $id))) {

            throw $this->createNotFoundException();
        }

        $allLogins = $user->getLoginRecords()->toArray();
        $logins = array_slice($allLogins, 0, 100);

        return $this->render('UserBundle:Admin:logins.html.twig', array(
            'user'      => $user,
            'logins'    => $logins,
        ));
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    private function addUserBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Users', array(
            'route' => 'Platformd_UserBundle_admin_index'
        ));

        return $this->getBreadcrumbs();
    }
}
