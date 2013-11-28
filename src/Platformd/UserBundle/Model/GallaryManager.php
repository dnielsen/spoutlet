<?php

namespace Platformd\UserBundle\Model;

use Doctrine\ORM\EntityRepository,
    Doctrine\ORM\EntityManager
;

use Symfony\Component\HttpFoundation\File\File;
use Gaufrette\Filesystem;

use Platformd\UserBundle\Entity\User,
    Platformd\UserBundle\Entity\Gallary,
    Platformd\UserBundle\ViewModel\gallary_index_data,
    Platformd\UserBundle\QueueMessage\AvatarResizeQueueMessage,
    Platformd\UserBundle\QueueMessage\AvatarFileSystemActionsQueueMessage
;

use HPCloud\HPCloudPHP;
class GallaryManager
{
    const IMAGE_CROP_MAX_WIDTH  = 512;
    const IMAGE_CROP_MAX_HEIGHT = 512;

    private $em;
    private $filesystem;
    private $gallaryRepo;
    private $publicBucket;
    private $privateBucket;
    private $s3;
    private $queueUtil;
    private $userManager;

    public function __construct(EntityManager $em, Filesystem $filesystem, $publicBucket, $privateBucket, $s3, $queueUtil, $userManager, $hpcloud_accesskey='', $hpcloud_secreatkey='', $hpcloud_tenantid='', $hpcloud_url='', $hpcloud_container='',$objectStorage='')
    {
        $this->em            = $em;
        $this->filesystem    = $filesystem;
        $this->galaryRepo    = $em->getRepository('UserBundle:Gallary');
        $this->publicBucket  = $publicBucket;
        $this->privateBucket = $privateBucket;
        $this->s3            = $s3;
        $this->queueUtil     = $queueUtil;
        $this->userManager   = $userManager;

        $this->objectStorage = $objectStorage;
        $this->hpcloud_container =   $hpcloud_container;
        $this->hpcloud_url = $hpcloud_url;
        $this->hpCloudObj = new HPCloudPHP($hpcloud_accesskey,$hpcloud_secreatkey,$hpcloud_tenantid);

    }

    public function save(Gallary $gallary)
    {
        if ($gallary->file) {
            $fileInfo = getimagesize($gallary->file);

            $gallary->setInitialFormat($gallary->file->guessExtension());
            $gallary->setInitialWidth($fileInfo[0]);
            $gallary->setInitialHeight($fileInfo[1]);
            $gallary->setUuid($this->upload($gallary->file, $gallary->getUser()));
        }

        // Gallary is valid
        if ($gallary->getUuid()) {
            $this->em->persist($gallary);
            $this->em->flush();
        }
    }

    public function findOneBy(array $criteria = array())
    {
        return $this->gallaryRepo->findOneBy($criteria);
    }

    public function getAllApprovedForUser(User $user)
    {
        return $this->gallaryRepo->findBy(array(
            'user'      => $user->getId(),
            'approved'  => true,
            'deleted'   => false,
            'cropped'   => true,
            'resized'   => true,
            'processed' => true,
        ));
    }

    public function getUnapprovedCountForUser(User $user)
    {
        return $this->gallaryRepo->getUnapprovedCountForUser($user);
    }

    public function getProcessingCountForUser(User $user)
    {
        return $this->gallaryRepo->getProcessingCountForUser($user);
    }

    public function findOneByUuidAndUser($uuid, User $user)
    {
        return $this->gallaryRepo->findOneBy(array(
            'uuid' => $uuid,
            'user' => $user->getId(),
        ));
    }

    protected function upload(File $file, User $user)
    {
        $fileUuid = $this->uuidGen();
        $this->checkUserUuid($user);

        $rawFilename = 'raw.'.$file->guessExtension();
        $opts = array('headers' => array('Cache-Control' => 'max-age=0'));
        $filename = $user->getUuid().'/'.$fileUuid.'/'.$rawFilename;
        
        $filename = $user->getUuid().".".$file->guessExtension();
        //$filename = $fileUuid;
         if($this->objectStorage == 'HpObjectStorage') {
  	  $this->hpCloudObj->SaveToObjectStorage($this->hpcloud_container,$filename,$file,Gallary::GALLARY_DIRECTORY_PREFIX);
         }
         else {
          $data = $this->filesystem->write($filename, file_get_contents($file),$opts);
         }

     //   $this->filesystem->write($user->getUuid().'/'.$fileUuid.'/'.$rawFilename, file_get_contents($file), $opts);

        unlink($file);

        return $fileUuid;
    }

    protected function uuidGen()
    {
        return str_replace("\n", '', `uuidgen -r`);
      // return rand(5, 15);
    }

    public function getSignedImageUrl($gallaryUuid, $filename, User $user)
    {
        $this->checkUserUuid($user);

        $filePath = $this->filesystem->getAdapter()->getDirectory().'/'.$user->getUuid().'/'.$gallaryUuid.'/'.$filename;
        return $this->s3->get_object_url($this->privateBucket, $filePath, '1 hour');
    }

    public function addToResizeQueue(User $user, $fileUuid, $extension, $width, $height, $x, $y)
    {
        $this->checkUserUuid($user);

        $message            = new AvatarResizeQueueMessage();
        $message->userUuid  = $user->getUuid();
        $message->fileUuid  = $fileUuid;
        $message->extension = $extension;
        $message->width     = $width;
        $message->height    = $height;
        $message->x         = $x;
        $message->y         = $y;

        $result = $this->queueUtil->addToQueue($message);

        return $result;

    }

    public function addToFilesystemActionsQueue($fileUuid, User $user, $action)
    {
        $message  = new AvatarFileSystemActionsQueueMessage();

        $this->checkUserUuid($user);

        $message->userUuid = $user->getUuid();
        $message->action   = $action;
        $message->fileUuid = $fileUuid;

        $result = $this->queueUtil->addToQueue($message);

        return $result;

    }

    public function getGallaryListingData(User $user, $size=84)
    {
        $this->checkUserUuid($user);

        $data    = new gallary_index_data();
        $gallarys = $this->getAllApprovedForUser($user);

        foreach($gallarys as $gallary) {
            $url = $this->getGallaryUrl($user->getUuid(), $size, $gallary->getUuid());

            $gallaryDetails = array(
                'id'  => $gallary->getId(),
                'url' => $url,
                'uuid' => $gallary->getUuid(),
            );

            if ($gallary == $user->getGallary()) {
                $data->activeGallary = $gallaryDetails;
            } else {
                $data->gallarys[] = $gallaryDetails;
            }
        }

        $data->unapprovedCount = $this->getUnapprovedCountForUser($user);
        $data->processingCount = $this->getProcessingCountForUser($user);

        return $data;
    }

    public function getUnapprovedGallarys($maxPerPage = 64, $currentPage = 1, &$pager = null, $locale = null)
    {
        $gallarys   = $this->gallaryRepo->getUnapprovedGallarys($maxPerPage, $currentPage, $pager, $locale);
        $galaryArr = array();

        foreach ($gallarys as $gallary) {

            $gallaryArr[] = array(
                'id' => $gallary->getId(),
                'url' => $this->getSignedImageUrl($gallary->getUuid(), '100x100.png', $gallary->getUser()),
                'userId' => $gallary->getUser()->getId(),
                'username' => $gallary->getUser()->getUsername(),
                'submitted' => $gallary->getCreatedAt(),
            );
        }

        return $gallaryArr;
    }

    public function processGallarys($gallaryIds, $processType)
    {
        if (count($gallaryIds) < 1) {
            return 0;
        }

        $approved = $processType == 'approve';
        $gallarys  = $this->gallaryRepo->findIdsIn($gallaryIds);

        foreach ($gallarys as $gallary) {

            // Avatar is already processed, so just set approval and move on
            if ($gallary->isProcessed()) {
                $gallary->setApproved($approved);
                $this->em->persist($gallary);
                continue;
            }

            $gallary->setApproved($approved);
            $gallary->setReviewed(true);
            $gallary->setDeleted(!$approved);
            $this->em->persist($gallary);

            if ($approved) {
                $this->addToFilesystemActionsQueue($gallary->getUuid(), $gallary->getUser(), AvatarFileSystemActionsQueueMessage::AVATAR_FILESYSTEM_ACTION_APPROVE);
            }
        }

        $this->em->flush();
    }

    public function getGallaryUrl($userUuid, $size, $fileUuid = 'by_size',$subDir= null)
    {
        if ($this->publicBucket == "platformd") {
            $cf = "http://media.alienwarearena.com";
        } else {
            $cf = ($this->objectStorage == "HpObjectStorage") ? $this->hpcloud_url.$this->hpcloud_container :  "https://s3.amazonaws.com/platformd-public" ;
        }
       if($subDir != "") {
           $url = $this->hpcloud_url.$this->hpcloud_container.'/'.Gallary::GALLARY_DIRECTORY_PREFIX;
           return $url.$userUuid;

       }
        
        //return $cf.'/'.Avatar::AVATAR_DIRECTORY_PREFIX.'/'.$userUuid.'/'.$fileUuid.'/'.$size.'x'.$size.'.png';
       return  $cf.'/'.Gallary::GALLARY_DIRECTORY_PREFIX.'/'.$userUuid.".jpg";
    

    }

    private function checkUserUuid($user)
    {
        if (!$user->getUuid()) {
            throw new \Exception('User [ ID => '.$user->getId().' ] tried to create an avatar but does not have a UUID set.');
        }
    }
}
