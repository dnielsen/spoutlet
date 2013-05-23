<?php

namespace Platformd\VideoBundle\Command;

use
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface
;

use Doctrine\Common\Collections\ArrayCollection;

use
    Platformd\SpoutletBundle\Entity\Gallery,
    Platformd\VideoBundle\Entity\YoutubeVideo
;

class ImportOldVideosCommand extends ContainerAwareCommand
{
    private $stdOutput;

    private $categorySlugMap = array(
        'プロモ' => 'jp-machinima',
        'オリジナル' => 'jp-original-productions',
        'テクニカル' => 'jp-skill-videos',
        'ALIENWARE 2012 BATTLEGROUNDS 対戦' => 'apj-battlegrounds',
    );

    protected function configure()
    {
        $this
            ->setName('pd:videos:import')
            ->setDescription('Imports videos from a .csv logfile following upload to YouTube')
            ->setHelp(<<<EOT
The <info>pd:videos:import</info> command uses a .csv logfile of youtube ids and imports them to the new video system:

  <info>php app/console pd:videos:import</info>
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container      = $this->getContainer();
        $em             = $container->get('doctrine')->getEntityManager();
        $galleryRepo    = $em->getRepository('SpoutletBundle:Gallery');
        $galleryCatRepo = $em->getRepository('SpoutletBundle:GalleryCategory');
        $siteRepo       = $em->getRepository('SpoutletBundle:Site');
        $allSites       = $siteRepo->findAll();
        $videoCategory  = $galleryCatRepo->findOneByName('video');
        $userManager    = $container->get('fos_user.user_manager');
        $fallbackUser   = $userManager->findUserByUsername('admin');
        $userRepo       = $em->getRepository('UserBundle:User');
        $youtubeManager = $container->get('platformd.model.youtube_manager');
        $videoRepo      = $em->getRepository('VideoBundle:YoutubeVideo');
        $japanSite      = $siteRepo->find(2);

        $this->stdOutput = $output;

        $erroredVideos = array();
        $cevoIdMap     = array();

        $videoGalleries = $galleryRepo->findAllGalleriesByCategory('video');

        $this->output(0);
        $this->output(0, 'Youtube Video Import Script');

        $this->output(2, 'Getting user IDs for videos...', false);

        if (($handle = fopen("/home/ubuntu/video_migration/movies_cevo_ids.csv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $movieId    = $data[0];
                $cevoUserId = $data[1];

                $cevoIdMap[$movieId] = $cevoUserId;
            }
        }

        $this->tick();

        $this->output(2, 'Getting existing categories...', false);

        $categories = array();

        foreach ($videoGalleries as $category) {
            $categories[$category->getName()] = $category->getId();
        }

        $this->tick();

        $this->output(2, 'Importing videos...');
        $this->output(0);

        if (($handle = fopen("/home/ubuntu/video_migration/processedVideos.csv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $movieId        = $data[0];
                $youtubeId      = $data[1];
                $categoryName   = $data[2];

                $this->output(4, 'Importing youtube ID - [ '.$youtubeId.' ]...');

                $categoryId = isset($categories[$categoryName]) ? $categories[$categoryName] : null;

                if (!$categoryId) {
                    $this->output(6, 'Category [ '.$categoryName.' ] does not exist - creating...', false);
                    $category = new Gallery();

                    $category->setName($categoryName);
                    $category->setCategories(array($videoCategory));
                    $category->setOwner($fallbackUser);
                    $category->setSites($allSites);

                    if (isset($this->categorySlugMap[$categoryName])) {
                        $category->setSlug($this->categorySlugMap[$categoryName]);
                        $category->setSites(array($japanSite));
                    }

                    $em->persist($category);
                    $em->flush();

                    $categories[$category->getName()] = $category->getId();

                    $this->tick();
                } else {
                    $category = $galleryRepo->find($categoryId);
                    $this->output(6, 'Category [ '.$category->getName().' ] found.');
                }

                $videoExists = $videoRepo->findOneByYoutubeId($youtubeId);

                if (!$videoExists) {

                    $this->output(6, 'Getting video info from YouTube...', false);

                    $videoDetails = $this->getVideoDetails($youtubeId);

                    $retryCount = 0;

                    while ((!$videoDetails || isset($videoDetails['error'])) && $retryCount < 10) {

                        if (isset($videoDetails['error'])) {
                            $errorCode = $videoDetails['error']['errors'][0]['code'];

                            if ($errorCode == 'ResourceNotFoundException' || $errorCode == 'ServiceForbiddenException') {
                                break;
                            }
                        }

                        $this->output(0);
                        $this->output(8, 'Problem with retrieving details. Retrying...', false);
                        $videoDetails = $this->getVideoDetails($youtubeId);
                        $retryCount++;
                    }

                    if (!$videoDetails) {
                        $this->output(0);
                        $this->output(8, 'Unable to retrieve details from YouTube.');
                        $this->output(0);

                        $erroredVideos['Unable To Retrieve Information'][] = $youtubeId;

                        continue;
                    }

                    if (isset($videoDetails['error'])) {

                        $error = $videoDetails['error']['errors'][0]['internalReason'];

                        $this->output(0);
                        $this->output(8, 'Unable to retrieve details from YouTube.');
                        $this->output(10, 'Error was [ '.$error.' ]');
                        $this->output(0);

                        $erroredVideos[$error][] = $youtubeId;

                        continue;
                    }

                    $this->tick();
                    $this->output(8, 'Video Title - '.$videoDetails['title']);

                    $this->output(6, 'Creating YoutubeVideo...', false);

                    $userId = isset($cevoIdMap[$movieId]) ? $cevoIdMap[$movieId] : null;

                    if ($userId) {
                        $videoUser = $userRepo->findOneByCevoUserId($userId);
                    }

                    $videoUser = $videoUser ?: $fallbackUser;

                    $youtubeVideo = new YoutubeVideo();

                    $youtubeVideo->setTitle($videoDetails['title']);
                    $youtubeVideo->setDescription($videoDetails['description']);
                    $youtubeVideo->setYoutubeId($youtubeId);
                    $youtubeVideo->setDuration($videoDetails['duration']);
                    $youtubeVideo->setAuthor($videoUser);
                    $youtubeVideo->setGalleries(array($category));
                    $youtubeVideo->setYoutubeLink('http://www.youtube.com/watch?v='.$youtubeId);

                    $youtubeManager->createVideo($youtubeVideo, true, false);

                    $this->tick();
                } else {

                    $videoExistsInCategory = $videoRepo->findOneByYoutubeIdInCategory($youtubeId, $category);

                    if ($videoExistsInCategory) {
                        $this->output(8, 'Video exists. Skipping.');
                    } else {
                        $this->output(8, 'Video exists. Adding to category.');

                        $videoExists->getGalleries()->add($category);
                        $em->persist($videoExists);
                        $em->flush();
                    }
                }

                $this->output(0);
            }
        }

        $this->output(2, 'Video import complete.');
        $this->output(0);

        if (count($erroredVideos) > 0) {
            $this->output(2, 'Some videos were not uploaded:');

            foreach ($erroredVideos as $errorMessage => $videos) {
                $this->output(4, 'Error - [ '.$errorMessage.' ]');
                foreach ($videos as $video) {
                    $this->output(6, $video);
                }
            }

            $this->output(0);
        }
    }

    protected function getVideoDetails($youtubeId)
    {
        $feedUrl = 'http://gdata.youtube.com/feeds/api/videos/'.$youtubeId.'?alt=jsonc&v=2';

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $feedUrl);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:'));

        $result = json_decode(curl_exec($curl), true);

        if(isset($result))
        {
            if(array_key_exists('error', $result))
            {
                return $result;
            }
        } else {
            return false;
        }

        return $result['data'];
    }
}
