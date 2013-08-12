<?php

namespace Platformd\SpoutletBundle\Command;

use
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Finder\Finder
;

use Platformd\UserBundle\Entity\Avatar;

class CevoAvatarImportCommand extends ContainerAwareCommand
{
    private $stdOutput;
    private $path;
    private $cevoIdUuidMap        = array();
    private $avatarUserMap        = array();
    private $exitAfterCurrentItem = false;
    private $errors               = array();
    private $debug                = false;

    private $sizes            = array(26,32,48,62,84,100,184,500);
    private $allowedMimeTypes = array(
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/jpg',
    );

    protected function configure()
    {
        $this
            ->setName('pd:avatar:importCevo')
            ->addArgument('path', InputArgument::REQUIRED, 'Directory containing avatar images.')
            ->addOption('debug', null, InputOption::VALUE_NONE, 'If set, the command will output more verbosely.')
            ->setDescription('Generates variously sized images from a CEVO user avatar collection and saves them to a "resized" directory in the parent of "path."')
            ->setHelp(<<<EOT
The <info>%command.name% /home/ubuntu/avatar_images</info> command iterates around the image files in /home/ubuntu/avatar_images and processes them into
avatars compatible with the new system. These avatars are saved in <info>{path}/../resized</info> and are in a directory structure ready to upload to S3.

The files are assumed to be named as follows: avatar_{cevoUserId}_{cevoAvatarId}_{width}_{height}_{filename}.{extension}, with an optional "_rejected" before the extension
if they were rejected under CEVO's system.

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
            $this->outputErrors();
            exit;
        }

        $this->errors[] = $message;
    }

    protected function resizeImage($imageResource, $size, $cropDetails, $userUuid, $fileUuid)
    {
        if ($this->debug) {
            $this->output(6, 'Generating '.$size.'x'.$size.' avatar...', false);
        }

        $resizedResource = ImageCreateTrueColor($size, $size);

        imagecopyresampled(
            $resizedResource,
            $imageResource,
            0, 0, $cropDetails['x'], $cropDetails['y'],
            $size, $size, $cropDetails['width'], $cropDetails['height']
        );

        $resizedFilename = rtrim($this->path, '/').'/../resized/'.$userUuid.'/'.$fileUuid.'/'.$size.'x'.$size.'.png';

        if(!file_exists(dirname($resizedFilename))) {
            mkdir(dirname($resizedFilename), 0775, true);
        }

        imagepng($resizedResource, $resizedFilename);

        if ($this->debug) {
            $this->tick();
        }

        return $resizedFilename;
    }

    public function upload($filePath, $filename)
    {
        $newFile = rtrim($this->path, '/').'/../resized/'.$filename;

        if(!file_exists(dirname($newFile))) {
            mkdir(dirname($newFile), 0775, true);
        }

        exec('cp '.$filePath.' '.$newFile);
        return true;
    }

    protected function uuidGen()
    {
        return str_replace("\n", '', `uuidgen -r`);
    }

    protected function writeCsvRow($filePath, $rowData)
    {
        $fp = fopen($filePath, 'a');
        fputcsv($fp, $rowData);
        fclose($fp);
    }

    protected function writeCevoCsvRow   (array $rowData) { $this->writeCsvRow(rtrim($this->path, '/').'/../avatar_map_files/cevo_user_id_uuid_map.csv' , $rowData); }
    protected function writeAvatarCsvRow (array $rowData) { $this->writeCsvRow(rtrim($this->path, '/').'/../avatar_map_files/user_avatar_map.csv'       , $rowData); }

    protected function readCevoMapCsv()
    {
        $filepath = rtrim($this->path, '/').'/../avatar_map_files/cevo_user_id_uuid_map.csv';

        if (!file_exists($filepath)) {
            return;
        }

        if (($handle = fopen($filepath, "r")) !== FALSE) {

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (isset($data[0]) && isset($data[1])) {
                    $this->cevoIdUuidMap[$data[0]] = $data[1];
                }
            }

            fclose($handle);
        }
    }

    protected function readAvatarMapCsv()
    {
        $filepath = rtrim($this->path, '/').'/../avatar_map_files/user_avatar_map.csv';

        if (!file_exists($filepath)) {
            return;
        }

        if (($handle = fopen($filepath, "r")) !== FALSE) {

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (isset($data[0]) && isset($data[1]) && isset($data[2]) && isset($data[3])) {
                    $this->avatarUserMap[$data[0]] = array(
                        'userUuid'   => $data[1],
                        'avatarUuid' => $data[2],
                        'avatarId'   => $data[3],
                    );
                }
            }

            fclose($handle);
        }
    }

    public function signal_handler($signal)
    {
        switch($signal) {
            case SIGTERM:
                $signalType = 'SIGTERM';
                break;
            case SIGKILL:
                $signalType = 'SIGKILL';
                break;
            case SIGINT:
                $signalType = 'SIGINT';
                break;
            case SIGHUP:
                $signalType = 'SIGHUP';
                break;
            default:
                $signalType = 'UNKNOWN_SIGNAL';
                break;
        }

        $this->output();
        $this->output(0, 'Caught signal ['.$signalType.']. Finishing processing...');
        $this->output();
        $this->exitAfterCurrentItem = true;
    }

    protected function outputErrors()
    {
        if (count($this->errors) > 0) {
            $this->output();
            $this->output(0, 'Errors:');
            $this->output();

            file_put_contents(rtrim($this->path, '/').'/../avatar_map_files/logfile', "Errors: \n", FILE_APPEND);

            foreach ($this->errors as $error) {
                $this->output(2, $error);
                file_put_contents(rtrim($this->path, '/').'/../avatar_map_files/logfile', $error."\n", FILE_APPEND);
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        declare(ticks = 1);
        pcntl_signal(SIGTERM, array($this, 'signal_handler'));
        pcntl_signal(SIGINT, array($this, 'signal_handler'));

        $this->stdOutput     = $output;
        $container           = $this->getContainer();
        $em                  = $container->get('doctrine')->getEntityManager();

        $this->debug  = $input->getOption('debug');

        $this->output(0);
        $this->output(0, 'PlatformD CEVO Avatar Importer');
        $this->output(0);

        $path = $input->getArgument('path');

        if (!is_dir($path)) {
            $this->error('Path [ '.$path.' ] is not a valid directory path', true);
        }

        $this->path = $path;
        $this->readCevoMapCsv();
        $this->readAvatarMapCsv();

        $this->output(0, 'Processing files in [ '.$path.' ].');

        $finder = new Finder();
        $finder->files()->in($path);

        $generated = 0;
        $skipped   = 0;
        $rejected  = 0;
        $invalid   = 0;
        $i         = 0;

        foreach ($finder as $file) {

            try {

                if ($this->exitAfterCurrentItem) {
                    exit;
                }

                $i++;

                if ($generated > 1000) {
                    exit;
                }

                $this->output(0, 'Avatar '.$i.' [ Generated = '.$generated.', Skipped = '.$skipped.', Rejected = '.$rejected.', Invalid = '.$invalid.' ]');

                $filename = $file->getFilename();

                $this->output(2, 'Image => { Filename: "'.$filename.'" }');

                $imagePath = $file->getRealpath();
                $imageData = getimagesize($imagePath);

                if (!in_array($imageData['mime'], $this->allowedMimeTypes)) {
                    $this->output(4, 'Image not a valid mime-type - [ '.$imageData['mime'].' ] - skipping.');
                    $this->output();
                    $invalid++;
                    continue;
                }

                if (false !== strpos($filename, '_rejected.')) {
                    $this->output(4, 'Image rejected under CEVO\'s system - skipping.');
                    $this->output();
                    $rejected++;
                    continue;
                }

                $fileNameDetails  = explode('_', $filename);
                $cevoUserId       = $fileNameDetails[1];
                $cevoAvatarId     = $fileNameDetails[2];
                $originalFilename = $fileNameDetails[5];

                if (isset($this->avatarUserMap[$cevoAvatarId])) {
                    $this->output(4, 'Avatar already processed => { User UUID = "'.$this->avatarUserMap[$cevoAvatarId]['userUuid'].'", Avatar UUID = "'.$this->avatarUserMap[$cevoAvatarId]['avatarUuid'].'" }');
                    $this->output();
                    $skipped++;
                    continue;
                }

                $userUuid         = isset($this->cevoIdUuidMap[$cevoUserId]) ? $this->cevoIdUuidMap[$cevoUserId] : $this->uuidGen();

                $width            = $imageData[0];
                $height           = $imageData[1];
                $landscape        = $width > $height;

                $cropDetails['x'] = $landscape ? ($width / 2) - ($height / 2) : 0;
                $cropDetails['y'] = 0;
                $cropDetails['width'] = $landscape ? $height : $width;
                $cropDetails['height'] = $landscape ? $height : $width;

                $imageResource = imagecreatefromstring(file_get_contents($imagePath));
                $fileUuid      = $this->uuidGen();

                $rawFilename = $userUuid.'/'.$fileUuid.'/raw.'.$file->getExtension();
                $this->upload($imagePath, $rawFilename);

                foreach ($this->sizes as $size) {

                    if ($this->debug) {
                        $this->output(4, 'Processing "'.$size.'x'.$size.'" avatar.');
                    }

                    $resizedFile = $this->resizeImage($imageResource, $size, $cropDetails, $userUuid, $fileUuid);
                }

                if ($this->debug) {
                    $this->output(4, 'Copying images to "by_size" directory.');
                }

                $avatarDirectory = rtrim($this->path, '/').'/../resized/'.$userUuid.'/'.$fileUuid.'/';
                $bySizeDirectory = rtrim($this->path, '/').'/../resized/'.$userUuid.'/by_size/';

                if(!file_exists($bySizeDirectory)) {
                    mkdir($bySizeDirectory, 0775, true);
                }

                exec('cp '.$avatarDirectory.'* '.$bySizeDirectory);

                if (!isset($this->cevoIdUuidMap[$cevoUserId])) {
                    $this->writeCevoCsvRow(array($cevoUserId, $userUuid));
                    $this->cevoIdUuidMap[$cevoUserId] = $userUuid;
                }

                if ($this->debug) {
                    $this->output(4, 'Creating avatar entity.');
                }

                $avatar = new Avatar();

                $avatar->setUuid($fileUuid);
                $avatar->setApproved(true);
                $avatar->setCropped(true);
                $avatar->setResized(true);
                $avatar->setProcessed(true);
                $avatar->setReviewed(true);
                $avatar->setDeleted(false);
                $avatar->setCropDimensions($cropDetails['width'].','.$cropDetails['height'].','.$cropDetails['x'].','.$cropDetails['y']);
                $avatar->setInitialFormat($file->getExtension());
                $avatar->setInitialWidth($width);
                $avatar->setInitialHeight($height);

                $em->persist($avatar);
                $em->flush();

                $this->writeAvatarCsvRow(array($cevoAvatarId, $userUuid, $fileUuid, $avatar->getId()));

                $this->output();

                $generated++;

            } catch (\Exception $e) {
                $this->error($e->getMessage());
                $this->outputErrors();
                exit;
            }
        }

        $this->output();
        $this->output(2, 'No more avatars in directory.');

        $this->outputErrors();

        $this->output(0);
    }
}
