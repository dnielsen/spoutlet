<?php

namespace Platformd\SpoutletBundle\Entity;

use Platformd\SpoutletBundle\Entity\HomepageBanner;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\ORM\EntityManager;
use Gaufrette\Filesystem;

/**
* 
*/
class HomepageBannerManager
{
   /**
    * @var Doctrine\ORM\EntityManager;
    */
    private $manager;

   /**
    * @var Gaufrette\Filesystem
    */
    private $filesystem;

    public function __construct(EntityManager $manager, Filesystem $filesystem)
    {
        $this->manager = $manager;
        $this->filesystem = $filesystem;
    }

    public function save(HomepageBanner $banner)
    {
        if ($banner->banner_file) {
          $banner->setBanner($this->upload($banner->banner_file, 'banner'));
        }

        if ($banner->thumb_file) {
          $banner->setThumb($this->upload($banner->thumb_file, 'thumb'));
        }

        $this->manager->persist($banner);
        $this->manager->flush();
    }

    protected function upload(File $file, $size)
    {
        if (!in_array($size, array('banner', 'thumb'))) {

        throw new \InvalidArgumentException(sprintf('Invalid size "%s"', $size));
        }

        $filename = $this->generateFilename($size, $file);
        $this->filesystem->write($filename, file_get_contents($file->getPathname()));

        return $filename;
    }

    protected function generateFilename($size, File $file)
    {

        return sprintf('%s-%s.%s', $size, sha1(uniqid()), $file->guessExtension());
    }
}