<?php

namespace Platformd\UserBundle\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response
;

use Platformd\SpoutletBundle\Controller\Controller,
    Platformd\UserBundle\Entity\User,
    Platformd\UserBundle\Entity\Avatar,
    Platformd\UserBundle\Form\Type\AvatarType,
    Platformd\UserBundle\QueueMessage\AvatarFileSystemActionsQueueMessage
;

class AvatarController extends Controller
{
    public function avatarAction(Request $request)
    {
        $this->checkSecurity();

        $avatarManager = $this->getAvatarManager();
        $data          = $avatarManager->getAvatarListingData($this->getUser(), 84);
        $newAvatar     = new Avatar();

        $newAvatar->setUser($this->getUser());

        $form = $this->createForm(new AvatarType(), $newAvatar);

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $newAvatar = $form->getData();
                $avatarManager->save($newAvatar);

                if ($newAvatar->getUuid()) {
                    return $this->redirect($this->generateUrl('avatar_crop', array(
                        'uuid' => $newAvatar->getUuid(),
                    )));
                } else {
                    $this->setFlash('error', 'platformd.user.avatars.invalid_avatar');
                }
            }
        }

        return $this->render('UserBundle:Avatar:avatars.html.twig', array(
            'data' => $data,
            'form' => $form->createView(),
        ));
    }

    public function cropAvatarAction($uuid = null)
    {
        $this->checkSecurity();

        if (!$uuid) {
            $this->setFlash('error', 'platformd.user.avatars.invalid_avatar');
            return $this->redirect($this->generateUrl('accounts_settings'));
        }

        $avatar = $this->findAvatar($uuid);

        if (!$avatar) {
            $this->setFlash('error', 'platformd.user.avatars.invalid_avatar');
            return $this->redirect($this->generateUrl('accounts_settings'));
        }

        if ($avatar->isCropped()) {
            $this->setFlash('error', 'platformd.user.avatars.already_cropped');
            return $this->redirect($this->generateUrl('accounts_settings'));
        }

        return $this->render('UserBundle:Avatar:cropAvatar.html.twig', array(
            'uuid'          => $uuid,
            'avatarUrl'     => $this->getAvatarManager()->getSignedImageUrl($uuid, 'raw.'.$avatar->getInitialFormat(), $this->getUser()),
        ));
    }

    public function processAvatarAction($uuid, $dimensions)
    {
        $this->checkSecurity();

        $avatarManager = $this->getAvatarManager();
        $avatar        = $this->findAvatar($uuid);
        $user          = $this->getUser();

        list($width, $height, $x, $y) = explode(',', $dimensions);

        $queued = $avatarManager->addToResizeQueue($user, $uuid, $avatar->getInitialFormat(), $width, $height, $x, $y);

        $avatar->setCropDimensions($dimensions);
        $avatar->setCropped(true);
        $avatarManager->save($avatar);

        if ($user->getAdminLevel()) {
            $this->setFlash('success', 'platformd.user.avatars.admin_submit_success');
        } else {
            $this->setFlash('success', 'platformd.user.avatars.submit_success');
        }

        return $this->redirect($this->generateUrl('accounts_settings'));
    }

    public function deleteAction(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-type', 'text/json; charset=utf-8');

        if (!$this->isGranted('ROLE_USER')) {
            $response->setContent(json_encode(array("success" => false)));
            return $response;
        }

        $params   = array();
        $content  = $request->getContent();

        if (empty($content)) {
            $response->setContent(json_encode(array("success" => false)));
            return $response;
        }

        $params   = json_decode($content, true);

        if (!isset($params['id'])) {
            $response->setContent(json_encode(array("success" => false)));
            return $response;
        }

        $avatarManager = $this->getAvatarManager();

        $avatar = $avatarManager->findOneBy(array(
            'id'   => $params['id'],
            'user' => $this->getUser()->getId(),
        ));

        if (!$avatar) {
            $response->setContent(json_encode(array("success" => false)));
            return $response;
        }

        $avatar->setDeleted(true);
        $avatarManager->save($avatar);

        $response->setContent(json_encode(array("success" => true)));
        return $response;
    }

    public function switchAction($uuid)
    {
        $this->checkSecurity();

        $avatar        = $this->findAvatar($uuid, false);
        $user          = $this->getUser();
        $avatarManager = $this->getAvatarManager();

        if (!$avatar) {
            $this->setFlash('error', 'platformd.user.avatars.switch_not_found');
            return $this->redirect($this->generateUrl('accounts_settings'));
        }

        if (!$avatar->isApproved()) {
            $this->setFlash('error', 'platformd.user.avatars.switch_error');
            return $this->redirect($this->generateUrl('accounts_settings'));
        }

        $avatarManager->addToFilesystemActionsQueue($avatar->getUuid(), $avatar->getUser(), AvatarFileSystemActionsQueueMessage::AVATAR_FILESYSTEM_ACTION_SWITCH);

        $avatar->setProcessed(false);
        $avatarManager->save($avatar);

        $this->setFlash('success', 'platformd.user.avatars.switch_success');

        return $this->redirect($this->generateUrl('accounts_settings'));
    }

    private function findAvatar($uuid, $exceptionOnNotFound = true)
    {
        $avatarManager = $this->getAvatarManager();
        $avatar        = $avatarManager->findOneByUuidAndUser($uuid, $this->getUser());

        if (!$avatar && $exceptionOnNotFound) {
            throw $this->createNotFoundException();
        }

        return $avatar;
    }

    protected function checkSecurity()
    {
        if (!$this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw new AccessDeniedException();
        }
    }
}
