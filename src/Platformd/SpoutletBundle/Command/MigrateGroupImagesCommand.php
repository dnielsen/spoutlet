<?php

namespace Platformd\SpoutletBundle\Command;

use
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface
;

use Doctrine\Common\Collections\ArrayCollection;

use Platformd\SpoutletBundle\Entity\GalleryMedia;
use Platformd\SpoutletBundle\Entity\ContentReport;

use DateTime;

class MigrateGroupImagesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('awa:groupimages:migrate')
            ->setDescription('Converts old group images to gallery media images')
            ->setHelp(<<<EOT
The <info>awa:groupimages:migrate</info> command takes all group images and migrates them to gallery media images:

  <info>php app/console awa:groupimages:migrate</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container      = $this->getContainer();
        $em             = $container->get('doctrine')->getEntityManager();
        $giRepo         = $em->getRepository('GroupBundle:GroupImage');
        $gmRepo         = $em->getRepository('SpoutletBundle:GalleryMedia');

        $groupImages = $giRepo->findAll();

        $output->writeLn('Migration started...');

        foreach ($groupImages as $groupImage) {
            try {
                $media = new GalleryMedia();
                $media->setTitle($groupImage->getTitle());
                $media->setCategory('image');
                $media->setDescription('');
                $media->setAuthor($groupImage->getAuthor());
                $media->setCreatedAt($groupImage->getCreatedAt());
                $media->setUpdatedAt($groupImage->getUpdatedAt());
                $media->setDeleted($groupImage->getDeleted());
                $media->setDeletedReason($groupImage->getDeletedReason());
                $media->setPublished(true);
                $media->setPublishedAt($groupImage->getCreatedAt());
                $media->setFeatured(false);
                $media->setImage($groupImage->getImage());
                $media->setGroups(array($groupImage->getGroup()));

                $em->persist($media);
                $em->flush();

                $this->migrateContentReports($groupImage->getContentReports(), $media, $em);

            } catch (\PDOException $e) {
                $output->writeLn(sprintf('Could not process all group images. Last group image processed was ID = %s, title = %s', $groupImage->getId(), $groupImage->getTitle()));
                $output->writeLn($e->getMessage());
                exit(0);
            }
        }

        $output->writeLn('Migration complete.');
    }

    private function migrateContentReports($reports, $media, $em)
    {
        foreach ($reports as $report) {
            $contentReport = new ContentReport();
            $contentReport->setReason($report->getReason());
            $contentReport->setReporter($report->getReporter());
            $contentReport->setReportedAt($report->getReportedAt());
            $contentReport->setSite($report->getSite());
            $contentReport->setDeleted($report->getDeleted());
            $contentReport->setGalleryMedia($media);

            $em->persist($contentReport);
            $em->flush();
        }
    }
}
