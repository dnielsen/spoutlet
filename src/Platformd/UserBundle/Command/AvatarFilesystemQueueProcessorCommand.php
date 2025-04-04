<?php

namespace Platformd\UserBundle\Command;

use
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface
;

use Platformd\UserBundle\QueueMessage\AvatarFileSystemActionsQueueMessage,
    Platformd\UserBundle\Entity\Avatar
;
use Platformd\SpoutletBundle\HPCloud\HPCloudPHP;

class AvatarFilesystemQueueProcessorCommand extends ContainerAwareCommand
{
    private $stdOutput;
    private $queueUtil;

    const DELAY_BETWEEN_ACTIONS_MILLISECONDS = 500;

    protected function configure()
    {
        $this
            ->setName('pd:avatar:processFilesystemQueue')
            ->setDescription('Processes the avatar filesystem actions that are currently queued')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command retrieves items from the avatar filesystem actions queue (e.g. moving approved avatars from the private
    bucket to the public one, or switching a user's active avatar) and processes them.

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
        $queueUtil    = $this->getContainer()->get('platformd.util.queue_util');

        $this->output(4, 'Deleting message from queue.');
        $this->output(6, ($queueUtil->deleteFromQueue($message) ? 'Message deleted successfully.' : 'Unable to delete message.'));
    }

    protected function addSwitchActionToQueue($fileUuid, $userUuid)
    {
        $message  = new AvatarFileSystemActionsQueueMessage();

        $message->userUuid = $userUuid;
        $message->action   = AvatarFileSystemActionsQueueMessage::AVATAR_FILESYSTEM_ACTION_SWITCH;
        $message->fileUuid = $fileUuid;

        $result = $this->queueUtil->addToQueue($message);

        return $result;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->stdOutput = $output;
        $container       = $this->getContainer();
        $em              = $container->get('doctrine')->getEntityManager();
        $avatarManager   = $container->get('platformd.model.avatar_manager');
        $s3              = $container->get('aws_s3');
        $this->queueUtil = $container->get('platformd.util.queue_util');
        $publicBucket    = $container->getParameter('s3_bucket_name');
        $privateBucket   = $container->getParameter('s3_private_bucket_name');
        $userRepo        = $em->getRepository('UserBundle:User');
        $userManager     = $container->get('fos_user.user_manager');
        $apiAuth         = $container->getParameter('api_authentication');
        $apiManager      = $container->get('platformd.user.api.manager');

        $this->output(0);
        $this->output(0, 'PlatformD Avatar Filesystem Actions Queue Processor');
        $this->output(0);

        $this->output(0, 'Processing queue for avatar actions...');
        $hpObject = 0;
        if($container->getParameter('object_storage') == 'HpObjectStorage')
        {
          $hpcloud_accesskey = $this->getContainer()->getParameter('hpcloud_accesskey');
          $hpcloud_secreatekey = $this->getContainer()->getParameter('hpcloud_secreatkey');
          $hpcloud_tenantid = $this->getContainer()->getParameter('hpcloud_tenantid');

          $hpcloud = new HPCloudPHP($hpcloud_accesskey, $hpcloud_secreatekey, $hpcloud_tenantid);
          $hpObject = 1 ;
        }

        while ($message = $this->queueUtil->retrieveFromQueue(new AvatarFileSystemActionsQueueMessage())) {

            usleep(self::DELAY_BETWEEN_ACTIONS_MILLISECONDS);

            $deleteMessage = true;

            $this->output();
            $this->output(2, $message);

            $action   = $message->action;

            if (!AvatarFileSystemActionsQueueMessage::isValidAction($action)) {
                $this->output(4, 'Invalid action "'.$action.'" specified.');
                $this->deleteMessageWithOutput($message);
                continue;
            }

            $userUuid = $message->userUuid;

            $user        = $userRepo->findOneByUuid($userUuid);

            if (!$user) {
                $this->output(4, 'User with UUID "'.$userUuid.'" could not be found.');
                $this->deleteMessageWithOutput($message);
                continue;
            }

            $fileUuid = $message->fileUuid;
            $avatar   = $avatarManager->findOneByUuidAndUser($fileUuid, $user);

            if (!$avatar) {
                $this->output(4, 'Avatar with UUID "'.$fileUuid.'" for user with UUID "'.$userUuid.'" could not be found.');
                $this->deleteMessageWithOutput($message);
                continue;
            }

            $this->output(4, 'Copying resources...');

            $sourceBucket = $action == AvatarFileSystemActionsQueueMessage::AVATAR_FILESYSTEM_ACTION_APPROVE ? $privateBucket : $publicBucket;
            if($hpObject == 1){
              $items = $hpcloud->get_object_list($sourceBucket,array('prefix' => Avatar::AVATAR_DIRECTORY_PREFIX));           
            } 
            // For AWS Storage
            else {
                $items = $s3->get_object_list($sourceBucket, array('prefix' => Avatar::AVATAR_DIRECTORY_PREFIX.'/'.$userUuid.'/'.$fileUuid));
            }
            $deleteItems  = array('objects'=>array());
            $switch       = $action == AvatarFileSystemActionsQueueMessage::AVATAR_FILESYSTEM_ACTION_SWITCH;

            if (count($items) < 1) {
                $this->error('No files found to copy in "'.$sourceBucket.'"');
                $this->deleteMessageWithOutput($message);
                continue;
            }
            
            if($hpObject == 1) {                               
            
              foreach ($items as $item) {
                  $filePath        = (string)$item;
                  $destinationPath = $action == AvatarFileSystemActionsQueueMessage::AVATAR_FILESYSTEM_ACTION_APPROVE ? $filePath : (Avatar::AVATAR_DIRECTORY_PREFIX.'/'.$userUuid.'/by_size/'.basename($filePath));
  
                  $deleteItems['objects'][]['key'] = $filePath;
  
                  $this->output(6, 'Copying "'.$filePath.'"...');
  
                  $source       = array('bucket' => $sourceBucket, 'filename' => $filePath);
                  $destination  = array('bucket' => $publicBucket, 'filename' => $destinationPath);
                  $opts         = array(
                      'headers' => array('Cache-Control' => 'max-age=0'),
                  );
  
                  $response = $hpcloud->copy_object($source, $destination, $opts);
  
                  if ($response ==false) {  
                      $switch = false;
                      $deleteMessage = false;
                      $this->error("File not found on S3.");               
                  }
              } // end for foreach
              
            } else {
            foreach ($items as $item) {
                $filePath        = (string)$item;
                $destinationPath = $action == AvatarFileSystemActionsQueueMessage::AVATAR_FILESYSTEM_ACTION_APPROVE ? $filePath : (Avatar::AVATAR_DIRECTORY_PREFIX.'/'.$userUuid.'/by_size/'.basename($filePath));

                $deleteItems['objects'][]['key'] = $filePath;

                $this->output(6, 'Copying "'.$filePath.'"...');

                $source       = array('bucket' => $sourceBucket, 'filename' => $filePath);
                $destination  = array('bucket' => $publicBucket, 'filename' => $destinationPath);
                $opts         = array(
                    'headers' => array('Cache-Control' => 'max-age=0'),
                );

                $response = $s3->copy_object($source, $destination, $opts);

                if ($response && !$response->isOk()) {

                    $switch = false;

                    if ($response->status == 404) {
                        $this->error("File not found on S3.");
                    } else {
                        $this->error($response->body->Error->Message);
                        $deleteMessage = false;
                    }
                }
            } // end for foreach
            } // end of AWS
            $avatar->setProcessed(true);
            $avatarManager->save($avatar);

            if ($action == AvatarFileSystemActionsQueueMessage::AVATAR_FILESYSTEM_ACTION_APPROVE) {

                $this->output(4, 'Deleting originals...', false);
                if($hpObject == 1) {
                  $response = $hpcloud->delete_objects($sourceBucket, $deleteItems);
                } else {
                  $response = $s3->delete_objects($sourceBucket, $deleteItems);
                }
                $this->addSwitchActionToQueue($fileUuid, $userUuid);
                
                 if($hpObject == 1) {
                 
                 } else { 
                    if ($response->isOk()) {
                        $this->tick();
    
                    } else {
                        $this->output();
                        $this->error($response->body->Error->Message);
                    }
                 }   
            } // end of if of action

            if ($action == AvatarFileSystemActionsQueueMessage::AVATAR_FILESYSTEM_ACTION_SWITCH) {
                $this->output(4, 'Settings user\' avatar...', false);
                $user->setAvatar($avatar);
                $userManager->updateUser($user);

                if ($apiAuth) {
                    $apiUpdated = $apiManager->updateRemoteUserData(array('uuid' => $user->getUuid(), 'custom_avatar' => true));
                    if (!$apiUpdated) {
                        $this->error('Unable to notify API of custom_avatar state.');
                    }
                }

                $this->tick();
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
