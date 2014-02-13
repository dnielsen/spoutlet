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
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

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

    public function __construct(Container $container, EntityManager $em, Filesystem $filesystem, $publicBucket, $privateBucket, $s3, $queueUtil, $userManager)
    {
        $this->em            = $em;
        $this->filesystem    = $filesystem;
        $this->galaryRepo    = $em->getRepository('UserBundle:Gallary');
        $this->publicBucket  = $publicBucket;
        $this->privateBucket = $privateBucket;
        $this->s3            = $s3;
        $this->queueUtil     = $queueUtil;
        $this->userManager   = $userManager;
        $this->container     = $container;
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
    
    private function getMediaManager()
    {
        return $this->container->get('platformd.media.entity_uploadStorage');
    }
    
    protected function upload(File $file, User $user)
    {
        $fileUuid = $this->uuidGen();
        $this->checkUserUuid($user);

        $rawFilename = 'raw.'.$file->guessExtension();
        $opts = array('headers' => array('Cache-Control' => 'max-age=0'));
        $filename = $user->getUuid().'/'.$fileUuid.'/'.$rawFilename;
        
        $filename = $user->getUuid().".".$file->guessExtension();
        $this->getMediaManager()->uploadToStorage($this->filesystem, $filename, $file, Gallary::GALLARY_DIRECTORY_PREFIX, $opts);

        unlink($file);
        return $fileUuid;
    }

    protected function uuidGen()
    {
         $data = openssl_random_pseudo_bytes(16);
         $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0010
         $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
         return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
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
        return $this->getMediaManager()->getMediaUrl($userUuid, $this->publicBucket, Gallary::GALLARY_DIRECTORY_PREFIX, $subDir = ($subDir != null ) ? Gallary::GALLARY_DIRECTORY_PREFIX : '','.jpg');
    }

    private function checkUserUuid($user)
    {
        if (!$user->getUuid()) {
            throw new \Exception('User [ ID => '.$user->getId().' ] tried to create an avatar but does not have a UUID set.');
        }
    }
}
