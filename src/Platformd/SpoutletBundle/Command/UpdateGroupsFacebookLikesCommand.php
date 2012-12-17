<?php

namespace Platformd\SpoutletBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class UpdateGroupsFacebookLikesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('awa:groups:updateFacebookLikes')
            ->setDescription('fetch new number of likes for groups and update groups with this fresh info')
            ->addArgument('updatedAt', InputArgument::OPTIONAL, 'number of seconds to filter last updated groups', 60 * 5) // 5 min
            ->addOption('timeout', 't', InputOption::VALUE_OPTIONAL, 'stop script after it exceeds x seconds', 90)
            ->setHelp(<<<EOT
The <info>awa:groups:updateFacebookLikes</info> command update number of FB likes for groups:

<info>php app/console awa:groups:updateFacebookLikes 3600 --timeout 90</info>

This example above will update groups which have not been updated for 3600 seconds, and will stop itself after it exceeds 90 seconds.

EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = time();
        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        $this->getContainer()->enterScope('request');
        $manager = $this->getContainer()->get('platformd.model.group_manager');
        $groups = $manager->findGroupsForFacebookLikesLastUpdatedAt($input->getArgument('updatedAt'));

        $i = 0;
        foreach ($groups as $group) {
            $total = $manager->updateFacebookLikes($group);
            sleep(3);
            $output->writeLn(sprintf('updated group: %d with %d likes', $group->getId(), $total));
            if ($i % 50 === 0) {
                $em->flush();
                $i = 0;
            }
            if (time() >= $start + $input->getOption('timeout')) {
                $output->writeLn('timeout');
                $em->flush();
                return;
            }
        }
        $em->flush();
    }
}
