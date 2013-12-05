<?php

namespace Platformd\UserBundle\Command;

use
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface
;

use Platformd\UserBundle\Entity\Avatar,
    Platformd\UserBundle\QueueMessage\AvatarResizeQueueMessage,
    Platformd\UserBundle\QueueMessage\AvatarFileSystemActionsQueueMessage
;
use Platformd\SpoutletBundle\HPCloud\HPCloudPHP;
class AvatarResizeQueueProcessorCommand extends ContainerAwareCommand
{
    private $stdOutput;
    private $searchManager;
    private $privateBucket;
    private $publicBucket;

    const DELAY_BETWEEN_AVATARS_MILLISECONDS = 500;

    private $sizes = array(26,32,48,62,84,100,184,500);

    protected function configure()
    {
        $this
            ->setName('pd:avatar:processResizeQueue')
            ->setDescription('Processes the avatar resize requests that are currently queued')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command retrieves items from the avatar resize queue and processes them, generating avatars of various sizes.

  <info>php %command.full_name%</info>
EOT
            );
    }

    protected function output($indentationLevel = 0, $message = null, $withNewLine = true) {

        if ($message === null) {
            $message = '';
        }

        if ($withNewLine) {
            $this->stdOutput->writeLn(str_repeat(' ', $indentationLevel).$message);
        } else {
            $this->stdOutput->write(str_repeat(' ', $indentationLevel).$message);
        }
    }

    protected function tick()
    {
        $this->output(0, '<info>✔</info>');
    }

    protected function error($message, $exit = false)
    {
        $this->output(0);
        $this->output(0, '<error>'.$message.'</error>');
        $this->output(0);

        if ($exit) {
            exit;
        }
    }

    protected function deleteMessageWithOutput($message)
    {
        $this->queueUtil    = $this->getContainer()->get('platformd.util.queue_util');

        $this->output(4, 'Deleting message from queue.');
        $this->output(4, ($this->queueUtil->deleteFromQueue($message) ? 'Message deleted successfully.' : 'Unable to delete message.'));
    }

    protected function resizeImage($imageResource, $size, $cropDetails)
    {
        $this->output(8, 'Generating '.$size.'x'.$size.' avatar...', false);

        $resizedResource = ImageCreateTrueColor($size, $size);

        imagecopyresampled(
            $resizedResource,
            $imageResource,
            0, 0, $cropDetails['x'], $cropDetails['y'],
            $size, $size, $cropDetails['width'], $cropDetails['height']
        );

        $tmpFilename = tempnam('/tmp', 'avatar_resized');
        imagepng($resizedResource, $tmpFilename);

        $this->tick();

        return $tmpFilename;
    }

    protected function upload($filePath, $userUuid, $fileUuid, $size, $autoApprove = false)
    {
        if($this->getContainer()->getParameter('object_storage') == 'HpObjectStorage') {
          
          $filename = $userUuid;
          $nm = Avatar::AVATAR_DIRECTORY_PREFIX;
          $this->output(8, 'Uploading "'.$size.'x'.$size.'" avatar to s3...', false);
          
          //$bucket = $autoApprove ? $this->publicBucket : $this->privateBucket;
          $bucket   = $this->publicBucket;
          $response = $this->hpcloud->create_object($bucket, $filename,array(
                'body'          => $jsonData,
                'contentType'   => 'image/png',      
                'subDir'        =>  $nm     
             ));
         if($response == true){
           $this->tick();
           return true;
         
         }  else {
            $this->output();
            return false;
         
           }   
        } else { 
        $filename = Avatar::AVATAR_DIRECTORY_PREFIX.'/'.$userUuid.'/'.$fileUuid.'/'.$size.'x'.$size.'.png';

        $this->output(8, 'Uploading "'.$size.'x'.$size.'" avatar to s3...', false);

        $bucket = $autoApprove ? $this->publicBucket : $this->privateBucket;

        $response = $this->s3->create_object($bucket, $filename, array(
            'fileUpload' => $filePath,
            'contentType' => 'image/png',
            'headers' => array('Cache-Control' => 'max-age=0'),
        ));

        if ($response->isOk()) {
           $this->tick();
           return true;
        } else {
            $this->output();
            return false;
        }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {      
        $this->stdOutput     = $output;
        $container           = $this->getContainer();
        $em                  = $container->get('doctrine')->getEntityManager();
        $this->avatarManager = $container->get('platformd.model.avatar_manager');
        $this->s3            = $container->get('aws_s3');
        $this->queueUtil     = $container->get('platformd.util.queue_util');
        $this->privateBucket = $container->getParameter('s3_private_bucket_name');
        $this->publicBucket  = $container->getParameter('s3_bucket_name');
        $userRepo            = $em->getRepository('UserBundle:User');
      
        $this->output(0);
        $this->output(0, 'PlatformD Avatar Resize Queue Processor');
        $this->output(0);

        $this->output(0, 'Processing queue for avatar resizing...');
        $hpObject = 0;
        if($this->getContainer()->getParameter('object_storage') == 'HpObjectStorage')  {
          $hpcloud_accesskey = $this->getContainer()->getParameter('hpcloud_accesskey');
          $hpcloud_secreatekey = $this->getContainer()->getParameter('hpcloud_secreatkey');
          $hpcloud_tenantid = $this->getContainer()->getParameter('hpcloud_tenantid');

          $this->hpcloud = new HPCloudPHP($hpcloud_accesskey, $hpcloud_secreatekey, $hpcloud_tenantid);
          $hpObject = 1 ;
        }
        while ($message = $this->queueUtil->retrieveFromQueue(new AvatarResizeQueueMessage())) {
            
            usleep(self::DELAY_BETWEEN_AVATARS_MILLISECONDS);

            $deleteMessage = true;

            $this->output();
            $this->output(2, $message);

            $userUuid    = $message->userUuid;
            $extension   = $message->extension;
            $fileUuid    = $message->fileUuid;
            $width       = $message->width;
            $height      = $message->height;
            $x           = $message->x;
            $y           = $message->y;
            $rawFilename = 'raw.'.$extension;
            $cropDetails = array('width' => $width, 'height' => $height, 'x' => $x, 'y' => $y);
            $user        = $userRepo->findOneByUuid($userUuid);

            if (!$user) {
                $this->output(4, 'User with UUID "'.$userUuid.'" could not be found.');
                $this->deleteMessageWithOutput($message);
                continue;
            }

            $avatar = $this->avatarManager->findOneByUuidAndUser($fileUuid, $user);

            if (!$avatar) {
                $this->output(4, 'Avatar with UUID "'.$fileUuid.'" for user with UUID "'.$userUuid.'" could not be found.');
                $this->deleteMessageWithOutput($message);
                continue;
            }

            $this->output(4, 'Retrieving image "'.$fileUuid.'/raw.'.$extension.'" from s3...', false);

            if($hpObject == 1) {
              $response_data ='';
              $filepath = Avatar::AVATAR_DIRECTORY_PREFIX.'/'.$userUuid;
              $response = $this->hpcloud->get_object($this->privateBucket, $filepath);
              $response_data = $response;
            } else {   
                $filepath = Avatar::AVATAR_DIRECTORY_PREFIX.'/'.$userUuid.'/'.$fileUuid.'/'.$rawFilename;
                $response = $this->s3->get_object($this->privateBucket, $filepath);
                $response_data = $response->isOk(); 
            }   
            if ($response_data) { // We retrieved the image file from S3

                $this->tick();
                $this->output(4, 'Processing image...');

                $imageData = $response->body;
                $imageResource = imagecreatefromstring($imageData);

                $autoApprove = $user->getAdminLevel();

                foreach ($this->sizes as $size) {
                    $this->output(6, 'Processing "'.$size.'x'.$size.'" avatar...');

                    $resizedFile = $this->resizeImage($imageResource, $size, $cropDetails);

                    if (!$this->upload($resizedFile, $userUuid, $fileUuid, $size, $autoApprove)) {
                        $this->output(4, 'Unable to upload image to s3 - skipping without deleting message from queue.');
                        continue 2;
                    }
                }

                $avatar->setResized(true);

                if ($user->getAdminLevel()) {
                    $this->output(4, 'Approving admin avatar...', false);
                    $avatar->setApproved(true);
                    $avatar->setReviewed(true);
                    $this->tick();

                    $em->persist($avatar);
                    $em->flush();

                    $this->output(4, 'Queuing avatar switch...', false);
                    $switchMessage  = new AvatarFileSystemActionsQueueMessage();
                    $switchMessage->userUuid = $userUuid;
                    $switchMessage->action   = AvatarFileSystemActionsQueueMessage::AVATAR_FILESYSTEM_ACTION_SWITCH;
                    $switchMessage->fileUuid = $fileUuid;
                    $result = $this->queueUtil->addToQueue($switchMessage);
                    $this->tick();
                    if($hpObject == 1) {
                      // Copy raw image to public bucket
                      $source       = array('bucket' => $this->privateBucket, 'filename' => $filepath);
                      $destination  = array('bucket' => $this->publicBucket, 'filename' => $filepath);
                      $response = $this->hpcloud->copy_object($source, $destination);

                      // Delete private bucket raw file as this is now in the public bucket
                      $response = $this->hpcloud->delete_object($this->privateBucket, $filepath);
                    }
                    // if its AWS
                    else {
                    // Copy raw image to public bucket
                    $source       = array('bucket' => $this->privateBucket, 'filename' => $filepath);
                    $destination  = array('bucket' => $this->publicBucket, 'filename' => $filepath);
                    $response = $this->s3->copy_object($source, $destination);

                    // Delete private bucket raw file as this is now in the public bucket
                    $response = $this->s3->delete_object($this->privateBucket, $filepath);
                    
                    }
                    
                } else {
                    $em->persist($avatar);
                    $em->flush();
                }

            }
              else {

                $this->output();
                $this->output(4, "An error occurred whilst downloading image file from S3:");
                if($hpObject ==0) {
                  if ($response) {
                      if ($response->status == 404) {
                          $this->error("File not found on S3.");
                      } else {
                          $this->error($response->body->Error->Message);
                          $deleteMessage = false;
                      }
                  }
                }
                else {
                          $this->error("File not found on S3.");               
                }
                
            }

            if ($deleteMessage) {
                $this->deleteMessageWithOutput($message);
            }
        }

        $this->output();
        $this->output(2, 'No more messages in queue.');

        $this->output(0);
    }
}
