<?php

namespace Platformd\UserBundle\Command;

use
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface
;
use Platformd\UserBundle\Entity\Avatar;
use Platformd\SpoutletBundle\HPCloud\HPCloudPHP;

class AvatarGenerateNewSizeCommand extends ContainerAwareCommand
{
    private $stdOutput;
    private $s3;
    private $publicBucket;
    private $privateBucket;
    private $hpObject;
    private $hpcloud;
    protected function configure()
    {
        $this
            ->setName('pd:avatar:generateNewSize')
            ->setDescription('Generates an avatar in the specified size for all existing avatars.')
            ->addArgument('size', InputArgument::REQUIRED, 'Size in pixels of the new avatar')
            ->setHelp(<<<EOT
The <info>%command.name%</info> processes the original image for each existing avatar using the original crop dimensions and gnerates
a new image with the specified size.

  <info>php %command.full_name% 256</info> - This will generate a new 256x256 image for each avatar.
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
        $this->output();
        $this->output(0, '<error>'.$message.'</error>');
        $this->output();

        if ($exit) {
            exit;
        }
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

    protected function processAvatar($user, $avatar, $size, $isCurrentAvatar = false)
    {
        $userUuid    = $user->getUuid();
        $avatarUuid  = $avatar->getUuid();
        $newFilename = $size.'x'.$size.'.png';
        
        $cropDetails = array();
        list($cropDetails['width'], $cropDetails['height'], $cropDetails['x'], $cropDetails['y']) = explode(',', $avatar->getCropDimensions());

        $rawFilename = 'raw.'.$avatar->getInitialFormat();
        $bucket      = $avatar->isProcessed() ? $this->publicBucket : $this->privateBucket;
        $directory   = $isCurrentAvatar ? 'by_size' : $avatarUuid;
        $basePath    = Avatar::AVATAR_DIRECTORY_PREFIX.'/'.$userUuid.'/'.$directory;
        $filepath    = $basePath.'/'.$rawFilename;
        if($this->hpObject ==0 ) {
          $alreadyExistsOnS3 = $this->s3->if_object_exists($bucket, $basePath.'/'.$newFilename);
        } else {
          $basePath = Avatar::AVATAR_DIRECTORY_PREFIX;
          $newFilename = $userUuid;
          $alreadyExistsOnS3 = $this->hpcloud->if_object_exists($bucket, $basePath.'/'.$newFilename);          
        } 
        if ($alreadyExistsOnS3) {
            $this->output(6, 'File already exists on s3 - skipping.');
            return false;
        }

        $this->output(6, 'Retrieving "'.$rawFilename.'" from s3...', false);
        if($this->hpObject == 0) {
          $response = $this->s3->get_object($bucket, $filepath);
        
        if (!$response->isOk()) { // We does not retrieved the image file from S3
            $this->output();
            $this->output(8, 'Error whilst downloading file from s3.');
            $error = $response->status == 404 ? "File not found on S3." : $response->body->Error->Message;
            $this->error($error);
            return false;
        }
        
        } else {
           $response = $this->hpcloud->get_object($bucket, $filepath);           
           if($response) {
            $this->output();
            $this->output(8, 'Error whilst downloading file from s3.');
            //$error = $response->status == 404 ? "File not found on S3." : $response->body->Error->Message;
            $error = 'File not found on S3';
            $this->error($error);
            return false;
           }     
        }
        

        $this->tick();
        $this->output(6, 'Processing image...');

        $imageData = $response->body;
        $imageResource = imagecreatefromstring($imageData);
        $resizedFile = $this->resizeImage($imageResource, $size, $cropDetails);

        $this->output(6, 'Uploading image...', false);
        if($this->hpObject == 0) {
          
          $response = $this->s3->create_object($bucket, $basePath.'/'.$newFilename, array(
              'fileUpload' => $resizedFile,
              'contentType' => 'image/png',
              'headers' => array('Cache-Control' => 'max-age=0'),
          ));
  
          if (!$response->isOk()) {
              $this->output();
              $this->output(8, 'Error whilst uploading file to s3.');
              $this->error($response->body->Error->Message);
              return false;
          }
        } else {
        
            $response = $this->hpcloud->create_object($bucket, $basePath.'/'.$newFilename, array(
                'fileUpload' => $resizedFile,
                'contentType' => 'image/png',
                'headers' => array('Cache-Control' => 'max-age=0'),
            ));
            if($response == false){
              $this->output();
              $this->output(8, 'Error whilst uploading file to s3.');
              $this->error('Error whilst uploading file to s3.');
              return false;
            }
          
        }
        $this->tick();
    }

    protected function processUserCurrentAvatar($user, $size)
    {
        $currentAvatar = $user->getAvatar();
        $newFilename   = $size.'x'.$size.'.png';

        if (!$currentAvatar) {
            return true;
        }
        if($this->hpObject == 0){
          $basePath = Avatar::AVATAR_DIRECTORY_PREFIX.'/'.$user->getUuid().'/by_size';
          $currentAvatarProcessed = $this->s3->if_object_exists($this->publicBucket, $basePath.'/'.$newFilename);
        } else {
          $basePath = Avatar::AVATAR_DIRECTORY_PREFIX;
          $newFilename = $user->getUuid();
          $currentAvatarProcessed = $this->s3->if_object_exists($this->publicBucket, $basePath.'/'.$newFilename);
        }
        
        if ($currentAvatarProcessed) {
            $this->output(6, 'Avatar already exists - skipping.');
            return true;
        }

        return $this->processAvatar($user, $currentAvatar, $size, true);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->stdOutput     = $output;
        $container           = $this->getContainer();
        $em                  = $container->get('doctrine')->getEntityManager();
        $avatarManager       = $container->get('platformd.model.avatar_manager');
        $avatarRepo          = $em->getRepository('UserBundle:Avatar');
        $this->s3            = $container->get('aws_s3');
        $this->publicBucket  = $container->getParameter('s3_bucket_name');
        $this->privateBucket = $container->getParameter('s3_private_bucket_name');

        $size                = abs((int) $input->getArgument('size'));
        $newFilename         = $size.'x'.$size.'.png';

        $this->output();
        $this->output(0, 'PlatformD Avatar Generator');
        $this->output();

        $this->output(2, 'Getting list of avatars...', false);
        $avatars = $avatarRepo->findAll();
        $this->tick();
        $this->output();
        $this->hpObject = 0;
        
        if($container->getParameter('object_storage') == 'HpObjectStorage') {
          $hpcloud_accesskey = $this->getContainer()->getParameter('hpcloud_accesskey');
          $hpcloud_secreatekey = $this->getContainer()->getParameter('hpcloud_secreatkey');
          $hpcloud_tenantid = $this->getContainer()->getParameter('hpcloud_tenantid');

          $this->hpcloud = new HPCloudPHP($hpcloud_accesskey, $hpcloud_secreatekey, $hpcloud_tenantid);
          $this->hpObject = 1 ;       
        }

        foreach ($avatars as $avatar) {

            $avataruuid = $avatar->getUuid();
            $this->output(2, 'Processing avatar "'.$avataruuid.'"');
            $user = $avatar->getUser();

            if (!$user) {
                $this->output(4, 'User not found - skipping.');
                continue;
            }

            $this->processAvatar($user, $avatar, $size);

            if ($user->getAvatar()) {
                $this->output(4, 'Processing avatar owner\'s current avatar...');
                $this->processUserCurrentAvatar($user, $size);
            }
        }

        $this->output();
        $this->output(2, 'No more avatars.');

        $this->output(0);
    }
}
