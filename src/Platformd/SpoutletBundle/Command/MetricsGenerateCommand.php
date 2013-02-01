<?php

namespace Platformd\SpoutletBundle\Command;

use
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface
;

use
    DateTime,
    DateTimeZone
;

class MetricsGenerateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('awa:metrics:generate')
            ->setDescription('Creates site metrics')
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, 'The type of metrics to build. Choices are: group, group-discussion. By default, will build all types.')
            ->addOption('full', null, InputOption::VALUE_NONE, 'Whether to build the metrics from the beginning of time. Also wipes all existing metrics. If not specified, builds metrics continuing where it had been left off.')
            ->setHelp(<<<EOT
The <info>awa:metrics:generate</info> command generates the site metrics :

  <info>php app/console awa:metrics:generate</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $full = $input->getOption('full') ? true : false;

        if ($type = strtolower($input->getOption('type'))) {
            $output->writeln(sprintf('Generating metrics for %s', $type));

            switch($type) {
                case 'group':
                    $this->generateGroupMetrics($full, $output);
                break;

                case 'group-discussion':
                    $this->generateGroupDiscussionMetrics($full, $output);
                break;

                default:
                    throw new \InvalidArgumentException(sprintf('Type "%s" is not supported.', $type));
            }
        } else {
            $this->generateMetrics($full, $output);
        }

        $output->writeln(sprintf('Metrics generated successfully.'));
    }

    protected function generateMetrics($full, OutputInterface $output)
    {
        $this->generateGroupMetrics($full, $output);
        $this->generateGroupDiscussionMetrics($full, $output);
    }

    protected function generateGroupMetrics($full, OutputInterface $output)
    {
        $output->writeln(sprintf('Generating Group Metrics...'));

        $this->getMetricManager()->generateGroupMetrics($full);

        $output->writeln(sprintf('Group Metrics generated successfully'));
    }

    protected function generateGroupDiscussionMetrics($full, OutputInterface $output)
    {
        $output->writeln(sprintf('Generating GroupDiscussions Metrics...'));

        $this->getMetricManager()->generateGroupDiscussionMetrics($full);

        $output->writeln(sprintf('GroupDiscussions Metrics generated successfully'));
    }

    /**
     * @return \Platformd\SpoutletBundle\Metric\MetricManager
     */
    protected function getMetricManager()
    {
        return $this->getContainer()->get('platformd.metric_manager');
    }
}
