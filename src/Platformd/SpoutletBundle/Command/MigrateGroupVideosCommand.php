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

use Platformd\VideoBundle\Entity\YoutubeVideo;

use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface as aclProvider,
    Symfony\Component\Security\Acl\Domain\ObjectIdentity,
    Symfony\Component\Security\Acl\Domain\UserSecurityIdentity,
    Symfony\Component\Security\Acl\Permission\MaskBuilder
;

class MigrateGroupVideosCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('awa:groupvideos:migrate')
            ->setDescription('Converts old group videos to youtube videos')
            ->setHelp(<<<EOT
The <info>awa:groupvideos:migrate</info> command takes all group videos and migrates them to youtube videos:

  <info>php app/console awa:groupvideos:migrate</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container      = $this->getContainer();
        $em             = $container->get('doctrine')->getEntityManager();
        $gvRepo         = $em->getRepository('GroupBundle:GroupVideo');
        $siteRepo       = $em->getRepository('SpoutletBundle:Site');
        $galRepo        = $em->getRepository('SpoutletBundle:Gallery');
        $ytRepo         = $em->getRepository('VideoBundle:YoutubeVideo');


        $groupVideos    = $gvRepo->findAll();
        $site           = $siteRepo->find(4);
        $gallery        = $galRepo->find(1);

        if(!$site) {
            $output->writeLn('Could not find a valid site. Exiting now.');
            exit(0);
        }

        if(!$gallery) {
            $output->writeLn('Could not find valid gallery. Exiting now.');
            exit(0);
        }

        $output->writeLn('Migration started...');

        foreach ($groupVideos as $groupVideo) {
            try {
                $duplicate = $ytRepo->findOneBy(array('title' => $groupVideo->getTitle()));

                if(!$duplicate) {
                    $video = new YoutubeVideo();
                    $video->setTitle($groupVideo->getTitle());
                    $video->setDescription('');
                    $video->setAuthor($groupVideo->getAuthor());
                    $video->setCreatedAt($groupVideo->getCreatedAt());
                    $video->setUpdatedAt($groupVideo->getUpdatedAt());
                    $video->setDeleted($groupVideo->getDeleted());
                    $video->setDeletedReason($groupVideo->getDeletedReason());
                    $video->setYoutubeId($groupVideo->getYouTubeVideoId());
                    $video->setYoutubeLink(sprintf('http://youtu.be/%s', $groupVideo->getYouTubeVideoId()));
                    $video->setDuration(0);
                    $video->setViews(0);
                    $video->setSite($site);
                    $video->setGroups(array($groupVideo->getGroup()));
                    $video->setGalleries(array($gallery));

                    $em->persist($video);

                    $em->flush();

                    $objectIdentity = ObjectIdentity::fromDomainObject($video);
                    $acl = $container->get('security.acl.provider')->createAcl($objectIdentity);
                    $securityIdentity = UserSecurityIdentity::fromAccount($video->getAuthor());

                    $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);
                    $container->get('security.acl.provider')->updateAcl($acl);
                } else {
                    $output->writeLn(sprintf('Video found with the same title. Youtube video will not be created from this video. Title is %s', $duplicate->getTitle()));
                }

            } catch (\PDOException $e) {
                $output->writeLn(sprintf('Could not process all group videos. Last group video processed was ID = %s, title = %s', $groupVideo->getId(), $groupVideo->getTitle()));
                $output->writeLn($e->getMessage());
                exit(0);
            }
        }

        $output->writeLn('Migration complete.');
    }
}
