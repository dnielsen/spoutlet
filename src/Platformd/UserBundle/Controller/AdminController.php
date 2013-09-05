<?php

namespace Platformd\UserBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;

use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;

use Platformd\UserBundle\Form\Type\EditUserFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Platformd\UserBundle\Form\Type\SuspendUserType;
use Platformd\UserBundle\Exception\ApiRequestException;
use Platformd\SpoutletBundle\Entity\Comment;
use Platformd\CEVOBundle\Api\ApiException;

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
            'pager'  => $pager,
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

        $commentsQuery = $this->getDoctrine()->getEntityManager()->getRepository('SpoutletBundle:Comment')->getFindCommentsForUserQuery($user);
        $pager = new PagerFanta(new DoctrineORMAdapter($commentsQuery));
        $pager->setMaxPerPage(50);

        $page = $this->getRequest()->get('comment_page', 1);
        $page = $page > $pager->getNbPages() ? $pager->getNbPages() : $page;
        $pager->setCurrentPage($page);

        return $this->render('UserBundle:Admin:edit.html.twig', array(
            'user'        => $user,
            'form'        => $form->createView(),
            'suspendForm' => $this->createForm(new SuspendUserType, $user)->createView(),
            'comments'    => $pager,
        ));
    }

    public function deleteCommentAjaxAction(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-type', 'text/json; charset=utf-8');

        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $response->setContent(json_encode(array("success" => false, "details" => 'insufficient privileges')));
            return $response;
        }

        $params   = array();
        $content  = $request->getContent();

        if (empty($content)) {
            $response->setContent(json_encode(array("success" => false, "details" => 'no content passed')));
            return $response;
        }

        $params   = json_decode($content, true);

        if (!isset($params['commentId'])) {
            $response->setContent(json_encode(array("success" => false, "details" => 'no comment id set')));
            return $response;
        }

        $em      = $this->getDoctrine()->getEntityManager();
        $comment = $em->getRepository('SpoutletBundle:Comment')->find($params['commentId']);

        if (!$comment) {
            $response->setContent(json_encode(array("success" => false, "details" => 'comment not found')));
            return $response;
        }

        $comment->setDeleted(true);
        $comment->setDeletedReason(Comment::DELETED_BY_ADMIN);

        $em->persist($comment);
        $em->flush();

        $this->removeUserArp($comment);

        $response->setContent(json_encode(array("success" => true)));
        return $response;
    }

    private function removeUserArp($comment)
    {
        try {
            $response = $this->get('pd.cevo.api.api_manager')->GiveUserXp('removecomment', $comment->getAuthor()->getCevoUserId());
        } catch (ApiException $e) {

        }
    }

    public function deleteCommentsAndBanAction($id)
    {
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->setFlash('error', 'YOu do not have the required privileges to perform this action.');
            return $this->redirect($this->generateUrl('Platformd_UserBundle_admin_edit', array('id' => $id)));
        }

        $manager = $this->get('fos_user.user_manager');

        if (!$user = $manager->findUserBy(array('id' => $id))) {
            throw $this->createNotFoundException(sprintf('Unable to retrieve user #%d', $id));
        }

        if ($this->getApiAuth()) {
            try {
                $this->getApiManager()->banUser($user);
            } catch (ApiRequestException $e) {
                $this->setFlash('error', 'There was a problem suspending this user. Please try again soon.');
                return $this->redirect($this->generateUrl('Platformd_UserBundle_admin_edit', array('id' => $id)));
            }
        }

        $user->setExpired(true);
        $this->getUserManager()->updateUser($user);

        $em             = $this->getDoctrine()->getEntityManager();
        $commentsQuery  = $em->getRepository('SpoutletBundle:Comment')->getAllActiveCommentsForUserQuery($user);
        $iterableResult = $commentsQuery->iterate();

        foreach ($iterableResult AS $row) {
            $row[0]->setDeleted(true);
            $row[0]->setDeletedReason(Comment::DELETED_BY_ADMIN_USER_BAN);

            $em->persist($row[0]);

            // Disabled as this requires a cURL request to CEVO for each and every comment removed.
            //$this->removeUserArp($row[0]);
        }

        $em->flush();

        $this->setFlash('success', 'This user is banned and all comments posted by them have been removed.');

        return $this->redirect($this->generateUrl('Platformd_UserBundle_admin_edit', array('id' => $id)));
    }

    public function updateAction(Request $request, $id)
    {
        $this->addUserBreadcrumb()->addChild('Update');
        $manager = $this->get('fos_user.user_manager');

        if (!$user = $manager->findUserBy(array('id' => $id))) {
            throw $this->createNotFoundException(sprintf('Unable to retrieve user #%d', $id));
        }

        $form = $this->createForm(new EditUserFormType(), $user, array(
            'allow_promote' => $this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'),
            'local_auth'    => $this->container->getParameter('local_auth'),
        ));

        $form->bindRequest($request);
        if ($form->isValid()) {

            try {
                $manager->updateUserAndApi($user);
                $this->setFlash('success', $this->trans('fos_user_admin_edit_success', array(
                    '%username%' => $user->getUsername()
                ), 'FOSUserBundle'));

                return $this->redirect($this->generateUrl('Platformd_UserBundle_admin_edit', array(
                    'id' => $id,
                )));
            } catch (ApiRequestException $e) {
                $this->setFlash('error', 'The system is currently unable to process your request. Please try again shortly.');
            }
        }

        return $this->render('UserBundle:Admin:edit.html.twig', array(
            'user'        => $user,
            'form'        => $form->createView(),
            'suspendForm' => $this->createForm(new SuspendUserType, $user)->createView(),
        ));
    }

    public function deleteAction($id)
    {
        $manager = $this->get('fos_user.user_manager');

        if (!($user = $manager->findUserBy(array('id' => $id))) || $user->isSuperAdmin()) {

            throw $this->createNotFoundException(sprintf('Unable to retrieve user #%d', $id));
        }

        $manager->deleteUser($user);

        $this->setFlash('success', $this->trans('fos_user_admin_delete_success', array(
            '%username%' => $user->getUsername()
        ), 'FOSUserBundle'));

        return $this->redirect($this->generateUrl('Platformd_UserBundle_admin_index'));
    }

    public function resetPasswordAction(Request $request, $id)
    {
        $manager = $this->get('fos_user.user_manager');
        $mailer  = $this->get('fos_user.mailer');

        if (!$user = $manager->findUserBy(array('id' => $id))) {

            throw $this->createNotFoundException();
        }

        $user->generateConfirmationToken();
        $mailer->sendResettedPasswordMessage($user);
        $user->setPasswordRequestedAt(new \DateTime());
        $manager->updateUser($user);

        $this->setFlash('success', $this->trans('fos_user_admin_resetted_password_success', array(
            '%email%' => $user->getEmail()
        ), 'FOSUserBundle'));

        return $this->redirect($this->generateUrl('Platformd_UserBundle_admin_edit', array(
            'id' => $id,
        )));
    }

    public function unapprovedAvatarsAction(Request $request)
    {
        if ($request->getMethod() == 'POST') {

            $processType = $request->request->get('process_type', null);

            if ($processType != 'approve' && $processType != 'reject') {
                $this->setFlash('error', 'platformd.admin.avatars.unapproved.form_error');
                $this->redirect($this->generateUrl('admin_unapproved_avatars'));
            }

            switch ($processType) {
                case 'approve':
                    $selectedIds = $request->request->get('all', array());
                    break;

                case 'reject':
                    $ids = $request->request->get('selected', array());

                    if (count($ids) == 0) {
                        $this->setFlash('error', 'platformd.admin.avatars.unapproved.no_avatars_selected');
                        $this->redirect($this->generateUrl('admin_unapproved_avatars'));
                    }

                    $selectedIds = array();

                    foreach ($ids as $avatarId) {
                        if ($avatarId != '') {
                            $selectedIds[] = $avatarId;
                        }
                    }

                    break;
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

        foreach ($avatars as $avatar) {

        }

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
            'user'   => $user,
            'logins' => $logins,
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
