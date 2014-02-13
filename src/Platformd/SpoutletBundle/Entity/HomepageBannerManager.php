<?php

namespace Platformd\SpoutletBundle\Entity;

use Platformd\SpoutletBundle\Entity\HomepageBanner;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\ORM\EntityManager;
use Gaufrette\Filesystem;
use Platformd\SpoutletBundle\Util\Image;
use Platformd\MediaBundle\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

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
 
    private $objectStorage = '';
    private $container;
    public function __construct(Container $container,EntityManager $manager, Filesystem $filesystem,$bannerDir='')
    {
        $this->manager = $manager;
        $this->filesystem = $filesystem;
        $this->saveDir = $bannerDir;    
        $this->container = $container; 
    }
    
    private function getMediaManager()
    {
        return $this->container->get('platformd.media.entity_uploadStorage');
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

        // round the image
        $roundedPath = $this->createRoundedImage($file, $size);

        $filename = $this->generateFilename($size, $file);             
        $this->getMediaManager()->uploadToStorage($this->filesystem, $filename, $roundedPath, $this->saveDir);
      
        // remove the founded path
        unlink($roundedPath);
        return $filename;
    }

    protected function generateFilename($size, File $file)
    {
        return sprintf('%s-%s.%s', $size, sha1(uniqid()), $file->guessExtension());
    }

    /**
     * Takes in a File object, rounds its corners based on size, and saves it to a file
     *
     * @param \Symfony\Component\HttpFoundation\File\File $file
     * @param string $size Either banner or thumb
     * @return string $tmpFilename The path where the rounded file was saved
     */
    private function createRoundedImage(File $file, $size)
    {
        $resource = $this->getImageResourceFromFile($file->getPathname());
        $radius = ($size == 'banner') ? 20 : 10;

        // create a fully-transparent rounded-corners file
        $roundedResource = Image::roundCorners($resource, $radius, 'ffffff', 127);

        // save the rounded file to a temporary location
        $tmpFilename = tempnam('/tmp', 'aa_banner');
        imagepng($roundedResource, $tmpFilename);

        return $tmpFilename;
    }

    /**
     * Returns an image resource for the given file
     *
     * @param $filename
     * @return resource
     */
     
    private function getImageResourceFromFile($filename)
    {
        list($sourceWidth, $sourceHeight, $sourceType) = getimagesize($filename);

        switch ($sourceType)
        {
          case IMAGETYPE_GIF:
            return imagecreatefromgif($filename);
          case IMAGETYPE_JPEG:
            return imagecreatefromjpeg($filename);
          case IMAGETYPE_PNG:
            return imagecreatefrompng($filename);
        }

        throw new \InvalidArgumentException(sprintf('Unknown image format "%s"', $sourceType));
    }
}