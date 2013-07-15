<?php

namespace Platformd\VideoBundle\DataFixtures\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Platformd\VideoBundle\Entity\YoutubeVideo;

class LoadVideos extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    private $container;
    private $manager;

    private static $youtubeBaseUrl = 'http://www.youtube.com/watch?v=';

    private static $youtubeIds = array(
      'oDwNZocSePI',
      'UumJaKxrOg4',
      '3krMncJwZDI',
      'qoyML98YDfg',
      '7eTPTb-NsNI',
      'Y0h6WIjZluM',
      'QFLyviLAlog',
      'NVrLxxBOmmY',
      'G2SrsbZBoeE',
      'kASRAFadPkU',
    );

    private function createVideo($id, $title, $user, $gallery, $featured=false)
    {
      $video = new YoutubeVideo();

      $video->setTitle($title);
      $video->setDescription('Automatically added video');
      $video->setAuthor($user);
      $video->setFeatured($featured);
      $video->setGalleries(array($gallery));
      $video->setYoutubeId($id);
      $video->setYoutubeLink(self::$youtubeBaseUrl.$id);

      $this->manager->persist($video);

      return $video;
    }

    private function resetAutoIncrementId()
    {
        $con = $this->manager->getConnection();

        $con
            ->prepare("ALTER TABLE `pd_videos_youtube` AUTO_INCREMENT = 1")
            ->execute();
    }

    public function load($manager)
    {
        $this->manager = $manager;

        $this->resetAutoIncrementId();

        $site      = $this->manager->getRepository('SpoutletBundle:Site')->find(1);
        $galleries = $this->manager->getRepository('SpoutletBundle:Gallery')->findAllGalleriesByCategoryForSite($site, 'video');
        $gallery   = $galleries[0];
        $user      = $this->container->get('fos_user.user_manager')->findUserByUsername('admin');
        $count     = 1;

        foreach (self::$youtubeIds as $id) {
          $video = $this->createVideo($id, 'Video '.$count, $user, $gallery);
          $count++;
        }

        $this->manager->flush();
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function getOrder()
    {
        return 4;
    }
}

?>
