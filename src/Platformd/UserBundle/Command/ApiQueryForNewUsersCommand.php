<?php

namespace Platformd\UserBundle\Command;

use
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface
;

use Platformd\UserBundle\Entity\User;
use Platformd\SpoutletBundle\Entity\ScriptLastRun;

class ApiQueryForNewUsersCommand extends ContainerAwareCommand
{
    const SCRIPT_ID = 'api_new_users_command';

    private $stdOutput;

    protected function configure()
    {
        $this
            ->setName('pd:users:getNewFromApi')
            ->setDescription('Queries the user API endpoint for all new users and creates local user records.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> makes a cURL call to the api endpoint asking for a list of users ordered most recently
created first. It iterates around them, creating local database user entries for each.

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
        $this->output();
        $this->output(0, '<error>'.$message.'</error>');
        $this->output();

        if ($exit) {
            exit;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->stdOutput   = $output;
        $container         = $this->getContainer();
        $em                = $container->get('doctrine')->getEntityManager();
        $userManager       = $container->get('fos_user.user_manager');
        $apiManager        = $container->get('platformd.user.api.manager');
        $scriptLastRunRepo = $em->getRepository('SpoutletBundle:ScriptLastRun');

        $apiAuth           = $container->getParameter('api_authentication');

        if (!$apiAuth) {
            $this->output();
            $this->output(0, 'API authentication is disabled.');
            $this->output();
            exit;
        }

        $exitScript        = false;
        $offset            = 0;
        $limit             = 100;

        $runDateTime       = new \DateTime();

        $this->output();
        $this->output(0, 'PlatformD New User Import');

        $hasRun = $scriptLastRunRepo->find(self::SCRIPT_ID);

        if (!$hasRun) {
            $hasRun = new ScriptLastRun(self::SCRIPT_ID);
            $em->persist($hasRun);
            $em->flush();
        }

        $since  = $hasRun->getLastRun();

        while ($exitScript === false) {

            $this->output();
            $this->output(2, 'Getting next ['.$limit.'] users...', false);

            $apiResult = $apiManager->getUserList($offset, $limit, 'created', $since);
            $itemCount = count($apiResult['items']);

            $this->tick();

            if ($itemCount < 1) {
                $exitScript = true;
                continue;
            }

            $userList = $apiResult['items'];

            foreach ($userList as $user) {

                $this->output(4, 'Looking up user "'.$user['uuid'].'" in database.');
                $dbUser = $userManager->findByUuid($user['uuid']);

                if ($dbUser) {
                    $this->output(6, 'User exists - skipping.');
                    continue;
                }

                $this->output(6, 'Creating user...', false);

                $dbUser = $userManager->createUser();

                $created   = $user['created'] ? new \DateTime($user['created']) : null;
                $updated   = $user['last_updated'] ? new \DateTime($user['last_updated']) : null;
                $birthdate = $user['birth_date'] ? new \DateTime($user['birth_date']) : null;
                $suspendedUntil = $user['suspended_until'] ? new \DateTime($user['suspended_until']) : null;

                $dbUser->setUsername($user['username']);
                $dbUser->setEmail($user['email']);
                $dbUser->setUuid($user['uuid']);
                $dbUser->setCreated($created);
                $dbUser->setUpdated($updated);
                $dbUser->setEnabled(true);
                $dbUser->setPassword('no_longer_used');
                $dbUser->setCountry($user['country']);
                $dbUser->setState($user['state']);
                $dbUser->setIpAddress($user['creation_ip_address']);
                $dbUser->setBirthdate($birthdate);
                $dbUser->setFirstname($user['first_name']);
                $dbUser->setLastname($user['last_name']);
                $dbUser->setExpired($user['banned']);
                $dbUser->setExpiredUntil($suspendedUntil);

                $userManager->updateUser($dbUser);

                $this->tick();
            }

            $offset += $limit;

        }

        $this->output();
        $this->output(2, 'No more users.');

        $hasRun->setLastRun($runDateTime);
        $em->persist($hasRun);
        $em->flush();

        $this->output(0);
    }
}
