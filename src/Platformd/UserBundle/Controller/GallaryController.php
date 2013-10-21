<?php

namespace Platformd\UserBundle\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response
;

use Platformd\SpoutletBundle\Controller\Controller,
    Platformd\GroupBundle\Entity\Group,
    Platformd\GroupBundle\Model\GroupManager,
    Platformd\UserBundle\Entity\User,
    Platformd\UserBundle\Entity\Gallary,
    Platformd\UserBundle\Form\Type\GallaryType,
    
    Platformd\UserBundle\QueueMessage\AvatarFileSystemActionsQueueMessage
;
use 
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException,
//    Symfony\Component\Security\Core\Exception\AccessDeniedException,
    Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
;
use HPCloud\HPCloudPHP;

class GallaryController extends Controller
{

    /**
     * @param $slug
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */

    public function gallaryAction($groupSlug, $eventSlug,Request $request)
    {
        $this->checkSecurity();
        if($this->isGallaryAttendee($groupSlug,$eventSlug)== 0){
	   throw new AccessDeniedException('Access Denied');
        }        
        
        $gallaryManager = $this->getGallaryManager();
      //  $data          = $gallaryManager->getGallaryListingData($this->getUser(), 84);
        $newGallary     = new Gallary();
        $data = array();

        $data['groupSlug'] = $groupSlug;
        $data['eventSlug'] = $eventSlug;

        $newGallary->setUser($this->getUser());

        $form = $this->createForm(new GallaryType(), $newGallary);
        if(isset($_GET['url_pic'])) {

         $hpcloud_accesskey = $this->container->getParameter('hpcloud_accesskey');
         $hpcloud_secreatekey = $this->container->getParameter('hpcloud_secreatkey');
         $hpcloud_tenantid = $this->container->getParameter('hpcloud_tenantid');

         $hpcloud = new HPCloudPHP($hpcloud_accesskey, $hpcloud_secreatekey, $hpcloud_tenantid);

        $url_pic = $_GET['url_pic'];       
        $url = $this->container->getParameter("hpcloud_url").$this->container->getParameter("hpcloud_container")."/"."images/gallary";

        $hpcloud->faceDetection($url_pic,$url);
     
        unset($hpcloud);
        $response = new Response();
    // $response->setContent(json_encode($data));
     return $response;
     }


        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $newGallary = $form->getData();
                $gallaryManager->save($newGallary);
	//	echo "data is".$data['groupSlug'];exit;
                if ($newGallary->getUuid()) {
                  //  return $this->redirect($this->generateUrl('avatar_crop', array(
                    //    'uuid' => $newAvatar->getUuid(),
                    //)));
                      
                   return $this->redirect($this->generateUrl('gallary_faceverify',array(
			'uuid' => $newGallary->getUuid(),
                        'gallaryId' => $newGallary->getId(),
                        'eventSlug' => $data['eventSlug'],
			'groupSlug' => $data['groupSlug']
			)));
                } else {
                    $this->setFlash('error', 'platformd.user.avatars.invalid_avatar');
                }
            }
        }

        return $this->render('UserBundle:Gallary:gallary.html.twig', array(
            'data' => $data,
            'form' => $form->createView(),
        ));
    }
    public function gallaryListAction($groupSlug,$eventSlug,Request $request){
        $em = $this->getDoctrine()->getEntityManager();
        $connection = $em->getConnection();
        $sql = "SELECT gallary.id , gallary.initial_format,fos_user.username,fos_user.uuid 
		FROM group_events_attendees_gallary
		LEFT JOIN gallary ON gallary.id = group_events_attendees_gallary.gallary_id
		LEFT JOIN fos_user ON fos_user.id = group_events_attendees_gallary.user_id";
       //echo $sql;exit;
        $statement = $connection->prepare($sql);
        $statement->execute();
        $results = $statement->fetchAll();
	//var_dump($results);exit;
        $gallaryUrl = ($this->container->getParameter('object_storage') == 'HpObjectStorage') ? $this->container->getParameter('hpcloud_url').$this->container->getParameter('hpcloud_container').'/images/gallary/' : 'https://s3.amazonaws.com/platformd-public/images/gallary/';
        return $this->render('UserBundle:Gallary:gallaryList.html.twig', array(
            'data' => $results,
            'eventSlug' => $eventSlug,
            'groupSlug' => $groupSlug,
            'gallaryUrl' => $gallaryUrl
        ));


    }
    private function isGallaryAttendee($groupSlug,$eventSlug){
	 $group = $this->getGroupManager()->getGroupBy(array('slug' => $groupSlug));

         if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
         }
         $groupEvent = $this->getGroupEventService()->findOneBy(array(
            'group' => $group->getId(),
            'slug' =>  $eventSlug,
        ));

        if (!$groupEvent) {
            throw new NotFoundHttpException('Event does not exist.');
        }

        $attendees = $this->getGroupEventService()->getAttendeeList($groupEvent);

       foreach($attendees as $attendee) {
		if($attendee['id'] == $this->getUser()->getId())
                   return 1;
        }
       return 0;

    }
    public function cropAvatarAction($uuid = null)
    {
    
    
    }

    public function faceVerificationAction($uuid = null, $gallaryId = null ,$eventSlug=null,$groupSlug=null,$render=null)
    {

      //$avatarUrl = $this->getAvatarManager()->getAvatarUrl($this->getUser()->getUuid(),0);
      // $group =  $this->getDoctrine()->getRepository('GroupBundle:Group')->findOneBy(array('slug' => $groupSlug));
      $group = $this->getGroupManager()->getGroupBy(array('slug' => $groupSlug));

        if (!$group) {
            throw new NotFoundHttpException('Group does not exist.');
        }
	
	
        $groupEvent = $this->getGroupEventService()->findOneBy(array(
            'group' => $group->getId(),
            'slug' => $eventSlug,
        ));

        if (!$groupEvent) {
            throw new NotFoundHttpException('Event does not exist.');
        }

        $attendees = $this->getGroupEventService()->getAttendeeList($groupEvent);
     
      $imageUrl = array();
      $name = '';
      $url = ($this->container->getParameter('object_storage') == 'HpObjectStorage') ? $this->container->getParameter('hpcloud_url').$this->container->getParameter('hpcloud_container').'/images/avatars/' : 'https://s3.amazonaws.com/platformd-public/images/avatars/';
      foreach($attendees as $attendee){
      
          if (@getimagesize($url.$attendee['uuid'])) {
	  $imageUrl[]= $url.$attendee['uuid'];
         }
       }

    if(isset($_GET['url_face'])) {
        $url_face = $_GET['url_face'];
        $user = $this->getDoctrine()
                   ->getRepository('UserBundle:User')->findOneBy(array('faceprintId'=>$url_face));	
       
        $value = $user->getFirstname()." ".$user->getLastname();
       
        $response = new Response();
        $response->setContent(json_encode($value));
	  // $response->setContent(json_encode($data));
        return $response;

       }
   
    // var_dump($imageUrl);exit;
     if(isset($_GET['url_pic_source'])) {

  //    echo "<pre>";var_dump($_GET);
        $hpcloud_accesskey = $this->container->getParameter('hpcloud_accesskey');
         $hpcloud_secreatekey = $this->container->getParameter('hpcloud_secreatkey');
         $hpcloud_tenantid = $this->container->getParameter('hpcloud_tenantid');

         $hpcloud = new HPCloudPHP($hpcloud_accesskey, $hpcloud_secreatekey, $hpcloud_tenantid);

   // $url_pic = $avatarUrl;
        $url_pic = $_GET['url_pic_source'];
       //echo $url_pic;exit;
      // We have to get the attendees image for this event       
      
       //$userUuid = $this->getUser()->getUuid();
     $url = $this->container->getParameter('hpcloud_url').$this->container->getParameter('hpcloud_container').'/images/gallarys';
     $hpcloud->faceVerification($url_pic,$url,$imageUrl);
     unset($hpcloud);    
     $response = new Response();
    // $response->setContent(json_encode($data));
     return $response;
     }
    
    $userUuid = $this->getUser()->getUuid();
    
    //echo $this->getAvatarManager()->getAvatarUrl($uuid,0);exit;i
    if($render == ""){

     return $this->render('UserBundle:Gallary:faceVerification.html.twig', array(
            'uuid'          =>   $uuid,
            'gallaryId'		=> $gallaryId,
            'gallaryUrl'     =>  $this->getGallaryManager()->getGallaryUrl($this->getUser()->getUuid(),0),
            'eventSlug'       => $eventSlug,
            'groupSlug'		=> $groupSlug,
            'name'		=> $name
        ));
    }
    else {
         
     return $this->render('UserBundle:Gallary:faceVerificationRender.html.twig', array(
            'uuid'          => $uuid,
            'gallaryUrl'     => $this->getGallaryManager()->getGallaryUrl($this->getUser()->getFaceprintImage(),0,0,"images/gallary"),
        ));

        }

    }
    
    public function faceverifySaveAction ($eventSlug=null,$groupSlug=null,$gallaryId=null)
    {
        
	$groupEvent = $this->getDoctrine()
    			->getRepository('EventBundle:GroupEvent')->findOneBySlug($eventSlug);

	$groupEventId = $groupEvent->getId();
        $userId= $this->getUser()->getId();

      	 $em = $this->getDoctrine()->getEntityManager();
	$connection = $em->getConnection();
        $sql = "INSERT INTO group_events_attendees_gallary (groupevent_id,user_id,gallary_id) VALUES ('".$groupEventId."','".$userId."','".$gallaryId."' )";
       //echo $sql;exit; 
	$statement = $connection->prepare($sql);
	$statement->execute();
	$this->setFlash('success', "Image Verified Sucessfully");
	return $this->redirect($this->generateUrl('group_event_gallary',array(
	'groupSlug' => $groupSlug,
	'eventSlug' => $eventSlug,
	)
	));

	//return $this->redirect('/'.$groupSlug.'/event/'.$eventSlug.'/gallary');
	echo "eventSlug:".$eventSlug."<br>:".$groupSlug."<br>:".$gallaryId;exit;
	 //echo "UUID:".$uuid;
     //echo "facePrintImage:".$facePrintImage;exit;
      $user = $this->getUser();
      $em = $this->getDoctrine()->getEntityManager();
      //echo $user->getId();
      //exit;
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

        $gallaryManager = $this->getGallaryManager();

        $gallary = $gallaryManager->findOneBy(array(
            'id'   => $params['id'],
            'user' => $this->getUser()->getId(),
        ));

        if (!$gallary) {
            $response->setContent(json_encode(array("success" => false)));
            return $response;
        }

        $gallary->setDeleted(true);
        $gallaryManager->save($gallary);

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

    private function findGallary($uuid, $exceptionOnNotFound = true)
    {
        $gallaryManager = $this->getGallaryManager();
        $gallary        = $gallaryManager->findOneByUuidAndUser($uuid, $this->getUser());

        if (!$gallary && $exceptionOnNotFound) {
            throw $this->createNotFoundException();
        }

        return $gallary;
    }

    protected function checkSecurity()
    {
        if (!$this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @return GroupManager
     */
    private function getGroupManager()
    {
        return $this->get('platformd.model.group_manager');
    }
}
