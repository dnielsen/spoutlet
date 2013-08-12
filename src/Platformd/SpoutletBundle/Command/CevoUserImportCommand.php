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
use Platformd\UserBundle\Entity\User;

use DateTime;

class CevoUserImportCommand extends ContainerAwareCommand
{
    private $stdOutput;
    private $directory;
    private $cevoIdUuidMap = array();
    private $avatarUserMap = array();
    private $debug = false;

    private $errors = array();

    protected function configure()
    {
        $this
            ->setName('pd:user:importCevo')
            ->addArgument('path', InputArgument::REQUIRED, 'CSV file containing CEVO user data dump.')
            ->addOption('debug', null, InputOption::VALUE_NONE, 'If set, the command will output more verbosely.')
            ->setDescription('Imports users from a csv file provided by CEVO.')
            ->setHelp(<<<EOT
The <info>%command.name% /home/ubuntu/users/users.csv</info> command iterates around the user data rows in /home/ubuntu/users/users.csv and updates/adds users to our database.

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

    protected function writeCevoCsvRow(array $rowData)
    {
        $this->writeCsvRow(rtrim($this->directory, '/').'/cevo_user_id_uuid_map.csv' , $rowData);
        $this->cevoIdUuidMap[$rowData[0]] = $rowData[1];
    }

    protected function readCevoMapCsv()
    {
        $filepath = rtrim($this->directory, '/').'/cevo_user_id_uuid_map.csv';

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
        $filepath = rtrim($this->directory, '/').'/user_avatar_map.csv';

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

    protected function outputUserData($userData)
    {
        $this->output();
        $this->output(2, 'User => { ');
        $this->output(8, 'cevoUserId   = '.$userData['cevoUserId']  );
        $this->output(8, 'username     = '.$userData['username']    );
        $this->output(8, 'password     = '.$userData['password']    );
        $this->output(8, 'email        = '.$userData['email']       );
        $this->output(8, 'created      = '.$userData['created']->format('Y-m-d'));
        $this->output(8, 'ipAddress    = '.$userData['ipAddress']   );
        $this->output(8, 'firstName    = '.$userData['firstName']   );
        $this->output(8, 'lastName     = '.$userData['lastName']    );
        $this->output(8, 'birthDate    = '.$userData['birthDate']->format('Y-m-d'));
        $this->output(8, 'phone        = '.$userData['phone']       );
        $this->output(8, 'country      = '.$userData['country']     );
        $this->output(8, 'state        = '.$userData['state']       );
        $this->output(8, 'allowContact = '.$userData['allowContact']);
        $this->output(8, 'dellOptIn    = '.$userData['dellOptIn']);
        $this->output(8, 'avatar       = '.$userData['avatar']      );
        $this->output(8, 'aboutMe      = '.$userData['aboutMe']     );
        $this->output(8, 'manufacturer = '.$userData['manufacturer']);
        $this->output(8, 'os           = '.$userData['os']          );
        $this->output(8, 'cpu          = '.$userData['cpu']         );
        $this->output(8, 'ram          = '.$userData['ram']         );
        $this->output(8, 'hardDrive    = '.$userData['hardDrive']   );
        $this->output(8, 'videoCard    = '.$userData['videoCard']   );
        $this->output(8, 'soundCard    = '.$userData['soundCard']   );
        $this->output(8, 'headphones   = '.$userData['headphones']  );
        $this->output(8, 'monitor      = '.$userData['monitor']     );
        $this->output(8, 'mouse        = '.$userData['mouse']       );
        $this->output(8, 'mousepad     = '.$userData['mousepad']    );
        $this->output(8, 'keyboard     = '.$userData['keyboard']    );
        $this->output(2, ' }');
    }

    protected function outputErrors()
    {
        if (count($this->errors) > 0) {
            $this->output();
            $this->output(0, 'Errors:');
            $this->output();

            foreach ($this->errors as $error) {
                $this->output(0, $error);
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->stdOutput     = $output;
        $container           = $this->getContainer();
        $em                  = $container->get('doctrine')->getEntityManager();
        $userRepo            = $em->getRepository('UserBundle:User');
        $avatarRepo          = $em->getRepository('UserBundle:Avatar');
        $userManager         = $container->get('fos_user.user_manager');

        $addedCount          = 0;

        $this->output(0);
        $this->output(0, 'PlatformD CEVO User Importer');
        $this->output(0);

        $path         = $input->getArgument('path');
        $this->debug  = $input->getOption('debug');

        if (!file_exists($path)) {
            $this->error('Path [ '.$path.' ] is not a valid path', true);
        }

        $this->directory = dirname($path);
        $this->readCevoMapCsv();
        $this->readAvatarMapCsv();

        $this->output(0, 'Processing users in [ '.$path.' ].');

        $iteration = 0;

        if (($handle = fopen($path, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 100000, ",")) !== FALSE) {

                try {

                    $iteration++;

                    // bypass header row
                    if ($iteration == 1) {
                        continue;
                    }

                    if ($this->debug) {
                        $this->output();
                    }

                    $this->output(2, 'Row '.$iteration.' [ '.$addedCount.' added ]');

                    $userData['cevoUserId']   = $data[0];
                    $userData['username']     = $data[1];
                    $userData['password']     = $data[2];
                    $userData['email']        = $data[3];
                    $userData['handle']       = $data[4];
                    $userData['created']      = DateTime::createFromFormat('U', $data[6]);
                    $userData['ipAddress']    = $data[7];
                    $userData['firstName']    = $data[11];
                    $userData['lastName']     = $data[12];
                    $userData['birthDate']    = DateTime::createFromFormat('Y-m-d', $data[13]);
                    $userData['phone']        = $data[14];
                    $userData['country']      = $data[16];
                    $userData['state']        = $data[17];
                    $userData['allowContact'] = $data[25];
                    $userData['dellOptIn']    = $data[26];
                    $userData['avatar']       = $data[34];
                    $userData['aboutMe']      = $data[35];
                    $userData['manufacturer'] = $data[37];
                    $userData['os']           = $data[38];
                    $userData['cpu']          = $data[39];
                    $userData['ram']          = $data[40];
                    $userData['hardDrive']    = $data[41];
                    $userData['videoCard']    = $data[42];
                    $userData['soundCard']    = $data[43];
                    $userData['headphones']   = $data[44];
                    $userData['monitor']      = $data[45];
                    $userData['mouse']        = $data[46];
                    $userData['mousepad']     = $data[47];
                    $userData['keyboard']     = $data[48];

                    if ($this->debug) {
                        $this->outputUserData($userData);
                    }

                    /*$userData['username'] = empty($userData['username']) ? $userData['handle'] : $userData['username'];

                    if (empty($userData['username']) || empty($userData['email'])) {
                        if ($this->debug) {
                            $this->output(4, 'Username or email blank - skipping.');
                        }

                        continue;
                    }

                    $user = $userRepo->findOneByUsername($userData['username']);

                    if (!$user) {
                        if ($this->debug) {
                            $this->output();
                            $this->output(2, 'User not found - creating.');
                        }

                        $user = new User();
                        $user->setUsername($userData['username']);
                        $user->setCevoUserId($userData['cevoUserId']);
                    } else {
                        if ($user->getCevoUserId() !== $userData['cevoUserId']) {
                            $this->error('User => { Username = '.$userData['username'].' } matched an existing user, but with a different CEVO User ID');
                            continue;
                        }

                        if ($this->debug) {
                            $this->output();
                            $this->output(2, 'User found - updating records.');
                        }
                    }

                    if ($user->getUuid()) {
                        $userUuid = $user->getUuid();
                    } elseif (!isset($this->cevoIdUuidMap[$userData['cevoUserId']])) {
                        $userUuid = $this->uuidGen();
                        $user->setUuid($userUuid);
                        $this->writeCevoCsvRow(array($userData['cevoUserId'], $userUuid));
                    } else {
                        $user->setUuid($this->cevoIdUuidMap[$userData['cevoUserId']]);
                    }

                    $user->setPassword($userData['password']);
                    $user->setEmail($userData['email']);
                    $user->setCreated($userData['created']);
                    $user->setIpAddress($userData['ipAddress']);
                    $user->setFirstName($userData['firstName']);
                    $user->setLastName($userData['lastName']);
                    $user->setBirthdate($userData['birthDate']);
                    $user->setPhoneNumber($userData['password']);
                    $user->setCountry($userData['country']);
                    $user->setState($userData['state']);
                    $user->setAboutMe($userData['aboutMe']);
                    $user->setManufacturer($userData['manufacturer']);
                    $user->setOperatingSystem($userData['os']);
                    $user->setCPU($userData['cpu']);
                    $user->setMemory($userData['ram']);
                    $user->setVideoCard($userData['videoCard']);
                    $user->setSoundCard($userData['soundCard']);
                    $user->setHardDrive($userData['hardDrive']);
                    $user->setHeadphones($userData['headphones']);
                    $user->setMouse($userData['mouse']);
                    $user->setMousePad($userData['mousepad']);
                    $user->setKeyboard($userData['keyboard']);
                    $user->setMonitor($userData['monitor']);
                    $user->setSubscribedGamingNews($userData['dellOptIn']);
                    $user->setSubscribedAlienwareEvents($userData['allowContact']);

                    $userManager->updateUser($user);

                    if (isset($this->avatarUserMap[$userData['avatar']])) {

                        if ($this->debug) {
                            $this->output();
                            $this->output(2, 'Setting user avatar.');
                        }

                        $avatarId = $this->avatarUserMap[$userData['avatar']]['avatarId'];

                        if ($avatar = $avatarRepo->find($avatarId)) {
                            $user->setAvatar($avatar);
                            $userManager->updateUser($user);
                            $avatar->setUser($user);
                            $em->persist($avatar);
                            $em->flush();
                        }
                    }*/

                    $addedCount++;

                    // contact prefs
                    // other required fields to make them a valid user

                } catch (\ErrorException $e) {
                    var_dump($data);
                    $this->outputErrors();
                    $this->error($e->getMessage());
                    exit;
                }
            }
        }

        $this->output();
        $this->output(2, 'No more users.');

        $this->outputErrors();

        $this->output(0);
    }
}
