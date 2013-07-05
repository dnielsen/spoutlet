<?php

namespace Platformd\SearchBundle\Command;

use
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\ArrayInput
;

class IndexAllDocumentsCommand extends ContainerAwareCommand
{
    private $stdOutput;
    private $searchManager;

    protected function configure()
    {
        $this
            ->setName('pd:search:indexAll')
            ->setDescription('Indexes all searchable entities and uploads data to Amazon CloudSearch')
            ->addOption('delete-all', null, InputOption::VALUE_NONE, 'If set, the command will first purge all documents from CloudSearch. Requires --confirm-delete to work.' )
            ->addOption('confirm-delete', null, InputOption::VALUE_NONE, 'Required to make --delete-all work.' )
            ->setHelp(<<<EOT
The <info>pd:search:indexAll</info> command iterates around all IndexableInterfaces and indexes them for searching via Amazon CloudSearch:

  <info>php app/console pd:search:indexAll</info>
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

    protected function error($message)
    {
        $this->output(0);
        $this->output(0, '<error>'.$message.'</error>');
        $this->output(0);
        exit;
    }

    protected function uploadBatch($batchData)
    {
        $this->output(4, 'Uploading batch to CloudSearch...', false);
        $result = $this->searchManager->document(json_encode($batchData));

        if (isset($result['status']) && $result['status'] == 'error') {
            $this->output(0);
            $this->output(0);
            $this->output(0, 'Amazon CloudSearch returned an error when uploading batch data:');
            var_dump($result);exit;
        }

        $this->tick();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->stdOutput     = $output;
        $container           = $this->getContainer();
        $em                  = $container->get('doctrine')->getEntityManager();
        $searchManager       = $container->get('platformd.model.search_manager');
        $this->searchManager = $searchManager;

        $deleteAll           = $input->getOption('delete-all');
        $confirmDelete       = $input->getOption('confirm-delete');

        $this->output(0);
        $this->output(0, 'PlatformD Search Indexer');
        $this->output(0);

        if ($deleteAll && !$confirmDelete) {
            $this->error('Please use --confirm-delete with --delete-all to confirm deletion of all search index documents.');
        }

        if ($deleteAll && $confirmDelete) {
            $command = $this->getApplication()->find('pd:search:deleteAll');

            $arguments = array(
                'command'          => 'pd:search:deleteAll',
                '--confirm-delete' => true,
            );

            $input = new ArrayInput($arguments);
            $returnCode = $command->run($input, $output);
        }

        $this->output(0, 'Getting searchable entity list...', false);
        $entities = $searchManager->getSearchableEntities();
        $this->tick();

        $batchData    = array();

        $this->output(2, 'Finding indexable entities...');

        foreach ($entities as $entityKey => $class) {

            $this->output(0);
            $this->output(4, 'Finding indexable instances of "'.$class.'"...');

            $repo = $em->getRepository($class);

            if (!$repo) {
                $this->error('Repository not found for "'.$class.'"');
            }

            $entityList = $repo->findAll();

            foreach ($entityList as $entity) {
                $this->output(6, 'Processing "'.$entity->getSearchTitle().'"');

                if (!$entity->getDeleteSearchDocument()) {

                    $entityIndexData    = $searchManager->getEntityIndexData($entity);
                    $updatedBatchData   = array_merge($batchData, $entityIndexData);

                    if (strlen(json_encode($updatedBatchData)) <= (4*1024*1024)) {
                        // Updated data is still within 4MB batch limit (Amazon limit to 5MB but this caused problems in testing) - append
                        $batchData = $updatedBatchData;
                    } else {

                        $this->output(0);

                        // Batch would be too large if we append - Upload processed data to CloudSearch and reset data
                        $this->uploadBatch($batchData);
                        $batchData = $entityIndexData;

                        $this->output(0);
                        $this->output(4, 'Continuing to index instances of "'.$class.'"');
                    }
                }
            }
        }

        // Process any remaining data
        if (count($batchData) > 0) {
            $this->output(0);
            $this->uploadBatch($batchData);
        }

        $this->output(0);
    }
}
