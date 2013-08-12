<?php

namespace Platformd\UserBundle\Model;

use Doctrine\ORM\EntityRepository,
    Doctrine\ORM\EntityManager
;

use Symfony\Component\HttpFoundation\File\File;
use Gaufrette\Filesystem;

use Platformd\UserBundle\Entity\User,
    Platformd\UserBundle\Entity\Avatar,
    Platformd\UserBundle\ViewModel\avatar_index_data,
    Platformd\UserBundle\QueueMessage\AvatarResizeQueueMessage,
    Platformd\UserBundle\QueueMessage\AvatarFileSystemActionsQueueMessage
;

class AvatarManager
{
    const IMAGE_CROP_MAX_WIDTH  = 512;
    const IMAGE_CROP_MAX_HEIGHT = 512;

    private $em;
    private $filesystem;
    private $avatarRepo;
    private $publicBucket;
    private $privateBucket;
    private $s3;
    private $queueUtil;
    private $userManager;

    public function __construct(EntityManager $em, Filesystem $filesystem, $publicBucket, $privateBucket, $s3, $queueUtil, $userManager)
    {
        $this->em            = $em;
        $this->filesystem    = $filesystem;
        $this->avatarRepo    = $em->getRepository('UserBundle:Avatar');
        $this->publicBucket  = $publicBucket;
        $this->privateBucket = $privateBucket;
        $this->s3            = $s3;
        $this->queueUtil     = $queueUtil;
        $this->userManager   = $userManager;
    }

    public function save(Avatar $avatar)
    {
        if ($avatar->file) {
            $fileInfo = getimagesize($avatar->file);

            $avatar->setInitialFormat($avatar->file->guessExtension());
            $avatar->setInitialWidth($fileInfo[0]);
            $avatar->setInitialHeight($fileInfo[1]);
            $avatar->setUuid($this->upload($avatar->file, $avatar->getUser()));
        }

        // Avatar is valid
        if ($avatar->getUuid()) {
            $this->em->persist($avatar);
            $this->em->flush();
        }
    }

    public function findOneBy(array $criteria = array())
    {
        return $this->avatarRepo->findOneBy($criteria);
    }

    public function getAllApprovedForUser(User $user)
    {
        return $this->avatarRepo->findBy(array(
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
        return $this->avatarRepo->getUnapprovedCountForUser($user);
    }

    public function getProcessingCountForUser(User $user)
    {
        return $this->avatarRepo->getProcessingCountForUser($user);
    }

    public function findOneByUuidAndUser($uuid, User $user)
    {
        return $this->avatarRepo->findOneBy(array(
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
        $this->filesystem->write($user->getUuid().'/'.$fileUuid.'/'.$rawFilename, file_get_contents($file), $opts);

        unlink($file);

        return $fileUuid;
    }

    protected function uuidGen()
    {
        return str_replace("\n", '', `uuidgen -r`);
    }

    public function getSignedImageUrl($avatarUuid, $filename, User $user)
    {
        $this->checkUserUuid($user);

        $filePath = $this->filesystem->getAdapter()->getDirectory().'/'.$user->getUuid().'/'.$avatarUuid.'/'.$filename;
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

    public function getAvatarListingData(User $user, $size=84)
    {
        $this->checkUserUuid($user);

        $data    = new avatar_index_data();
        $avatars = $this->getAllApprovedForUser($user);

        foreach ($avatars as $avatar) {
            $url = $this->getAvatarUrl($user->getUuid(), $size, $avatar->getUuid());

            $avatarDetails = array(
                'id'  => $avatar->getId(),
                'url' => $url,
                'uuid' => $avatar->getUuid(),
            );

            if ($avatar == $user->getAvatar()) {
                $data->activeAvatar = $avatarDetails;
            } else {
                $data->avatars[] = $avatarDetails;
            }
        }

        $data->unapprovedCount = $this->getUnapprovedCountForUser($user);
        $data->processingCount = $this->getProcessingCountForUser($user);

        return $data;
    }

    public function getUnapprovedAvatars($maxPerPage = 64, $currentPage = 1, &$pager = null, $locale = null)
    {
        $avatars   = $this->avatarRepo->getUnapprovedAvatars($maxPerPage, $currentPage, $pager, $locale);
        $avatarArr = array();

        foreach ($avatars as $avatar) {

            $avatarArr[] = array(
                'id' => $avatar->getId(),
                'url' => $this->getSignedImageUrl($avatar->getUuid(), '100x100.png', $avatar->getUser()),
                'userId' => $avatar->getUser()->getId(),
                'username' => $avatar->getUser()->getUsername(),
                'submitted' => $avatar->getCreatedAt(),
            );
        }

        return $avatarArr;
    }

    public function processAvatars($avatarIds, $processType)
    {
        if (count($avatarIds) < 1) {
            return 0;
        }

        $approved = $processType == 'approve';
        $avatars  = $this->avatarRepo->findIdsIn($avatarIds);

        foreach ($avatars as $avatar) {

            // Avatar is already processed, so just set approval and move on
            if ($avatar->isProcessed()) {
                $avatar->setApproved($approved);
                $this->em->persist($avatar);
                continue;
            }

            $avatar->setApproved($approved);
            $avatar->setReviewed(true);
            $avatar->setDeleted(!$approved);
            $this->em->persist($avatar);

            if ($approved) {
                $this->addToFilesystemActionsQueue($avatar->getUuid(), $avatar->getUser(), AvatarFileSystemActionsQueueMessage::AVATAR_FILESYSTEM_ACTION_APPROVE);
            }
        }

        $this->em->flush();
    }

    public function getAvatarUrl($userUuid, $size, $fileUuid = 'by_size')
    {
        if ($this->publicBucket == "platformd") {
            $cf = "http://media.alienwarearena.com";
        } else {
            $cf = "http://mediastaging.alienwarearena.com";
        }

        return $cf.'/'.Avatar::AVATAR_DIRECTORY_PREFIX.'/'.$userUuid.'/'.$fileUuid.'/'.$size.'x'.$size.'.png';
    }

    private function checkUserUuid($user)
    {
        if (!$user->getUuid()) {
            throw new \Exception('User [ ID => '.$user->getId().' ] tried to create an avatar but does not have a UUID set.');
        }
    }
}
