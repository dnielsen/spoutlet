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
use Platformd\SpoutletBundle\HPCloud\HPCloudPHP;

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
        if(isset($_GET['url_pic'])) {
            $hpcloud_accesskey = $this->container->getParameter('hpcloud_accesskey');
            $hpcloud_secreatekey = $this->container->getParameter('hpcloud_secreatkey');
            $hpcloud_tenantid = $this->container->getParameter('hpcloud_tenantid');
            $hpcloud = new HPCloudPHP($hpcloud_accesskey, $hpcloud_secreatekey, $hpcloud_tenantid);

            $url_pic = $_GET['url_pic'];
            $url = $this->container->getParameter("hpcloud_url").$this->container->getParameter("hpcloud_container")."/"."images/avatar";
            //$url = 'https://region-a.geo-1.objects.hpcloudsvc.com/v1/10873218563681/cloudcamp/images/avatar';
            $hpcloud->faceDetection($url_pic,$url);

            unset($hpcloud);
            $response = new Response();
            // $response->setContent(json_encode($data));
            return $response;
        }


        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $newAvatar = $form->getData();
                $avatarManager->save($newAvatar);

                if ($newAvatar->getUuid()) {
                    return $this->redirect($this->generateUrl('avatar_facedetect',array('uuid' => $newAvatar->getUuid(),)));
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
//            'avatarUrl'     => $this->getAvatarManager()->getSignedImageUrl($uuid, 'raw.'.$avatar->getInitialFormat(), $this->getUser()),
            'avatarUrl'     => $this->getAvatarManager()->getAvatarUrl($this->getUser()->getUuid(),0),

        ));
    }

    public function faceDetectAction($uuid = null,$render=null)
    {

        //$avatarUrl = $this->getAvatarManager()->getAvatarUrl($this->getUser()->getUuid(),0);

        if(isset($_GET['url_pic'])) {


            $hpcloud_accesskey = $this->container->getParameter('hpcloud_accesskey');
            $hpcloud_secreatekey = $this->container->getParameter('hpcloud_secreatkey');
            $hpcloud_tenantid = $this->container->getParameter('hpcloud_tenantid');

            $hpcloud = new HPCloudPHP($hpcloud_accesskey, $hpcloud_secreatekey, $hpcloud_tenantid);

            $url_pic = $_GET['url_pic'];

            //$userUuid = $this->getUser()->getUuid();
            // $url = 'https://region-a.geo-1.objects.hpcloudsvc.com/v1/10873218563681/cloudcamp/images/avatar';
            $url = $this->container->getParameter("hpcloud_url").$this->container->getParameter("hpcloud_container")."/"."images/avatar";

            $hpcloud->faceDetection($url_pic,$url);
            unset($hpcloud);
            $response = new Response();
            // $response->setContent(json_encode($data));
            return $response;
        }

        $userUuid = $this->getUser()->getUuid();

        // echo $this->getAvatarManager()->getAvatarUrl($uuid,0);exit;
        if($render == ""){

            return $this->render('UserBundle:Avatar:faceDetectAvatar.html.twig', array(
                'uuid'          => $uuid,
                'avatarUrl'     => $this->getAvatarManager()->getAvatarUrl($this->getUser()->getUuid(),0),
            ));
        }
        else {

            return $this->render('UserBundle:Avatar:faceDetectAvatarRender.html.twig', array(
                'uuid'          => $uuid,
                'avatarUrl'     => $this->getAvatarManager()->getAvatarUrl($this->getUser()->getFaceprintImage(),0,0,"images/avatar"),
            ));

        }

    }

    public function facePrintAction ($uuid = null, $facePrintId = null, $facePrintImage = null)
    {

        $user = $this->getUser();
        $em = $this->getDoctrine()->getEntityManager();

        $user->setFacePrintId($facePrintId);
        $user->setFacePrintImage($facePrintImage);
        $em->persist($user);
        // $user->save($user);
        $em->flush();
        // redirected to face Crop
        $this->setFlash('success', "Image Set Sucessfully");
        return $this->redirect($this->generateUrl('avatars'));
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
