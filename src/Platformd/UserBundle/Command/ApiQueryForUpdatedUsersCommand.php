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

class ApiQueryForUpdatedUsersCommand extends ContainerAwareCommand
{
    const SCRIPT_ID = 'api_updated_users_command';

    private $stdOutput;

    protected function configure()
    {
        $this
            ->setName('pd:users:getUpdatedFromApi')
            ->setDescription('Queries the user API endpoint for all updated users and updates local user records.')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Updates all user records')
            ->setHelp(<<<EOT
The <info>%command.name%</info> makes a cURL call to the api endpoint asking for a list of users ordered most recently
updated first, updating local database user entries for each.

  <info>php %command.full_name%</info> - Updates users since last update.
  <info>php %command.full_name% --all</info> - Updates all users.
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

        $response          = 200;
        $offset            = 0;
        $limit             = 100;

        $runDateTime       = new \DateTime();

        if ($input->getOption('all')) {
            $since = null;
        } else {
            $hasRun = $scriptLastRunRepo->find(self::SCRIPT_ID);

            if (!$hasRun) {
                $hasRun = new ScriptLastRun(self::SCRIPT_ID);
                $em->persist($hasRun);
                $em->flush;
            }

            $since  = $hasRun->getLastRun();
        }

        $this->output();
        $this->output(0, 'PlatformD User Updater');
        $this->output();

        while ($response == 200) {

            $this->output(2, 'Getting next ['.$limit.'] users...', false);

            $apiResult = $apiManager->getUserList($offset, $limit, 'lastUpdated', $since);
            $response  = $apiResult['metaData']['status'];

            $this->tick();

            if ($response == 200) {
                $userList = $apiResult['items'];

                foreach ($userList as $user) {

                    $username = $user['username'];

                    $this->output(4, 'Looking up user "'.$username.'" in database...');
                    $dbUser = $userManager->findUserByUsername($username);

                    if (!$dbUser) {
                        $this->output(4, 'User not in database - skipping.');
                        continue;
                    }

                    $dbUser->setUsername($username);
                    $dbUser->setEmail($user['email']);
                    $dbUser->setUuid($user['uuid']);
                    $dbUser->setCreated($user['created']);
                    $dbUser->setUpdated($user['lastUpdated']);
                    $dbUser->setEnabled(true);
                    $dbUser->setPassword('no_longer_used');

                    $em->persist($dbUser);
                    $em->flush();
                }
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
