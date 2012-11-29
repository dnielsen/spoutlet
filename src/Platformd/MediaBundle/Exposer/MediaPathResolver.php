<?php

namespace Platformd\MediaBundle\Exposer;

use MediaExposer\PathResolver;
use Knp\MediaBundle\Entity\Media;

/**
 * Media exposer path resolver that points to a public S3
 */
class MediaPathResolver implements PathResolver
{
    private $bucketName;

    private $prefix;

    public function __construct($bucketName, $prefix)
    {
        $this->bucketName = $bucketName;
        $this->prefix = $prefix;
    }

    public function supports($media, array $options)
    {
        return ($media instanceof Media);
    }

    /**
     * @param \Knp\MediaBundle\Entity\Media $media
     * @param array $options
     * @return string
     */
    public function getPath($media, array $options)
    {
        if (!$media->getFilename()) {
            return false;
        }

        return $media->getFilename();


        /* old method of differentiating between locally stored images and s3 located ones. Not needed as all media images will be on s3 now.
        // used when you're going to pipe it into imagine
        if (isset($options['local']) && $options['local']) {
            return $media->getFilename();
        }

        return sprintf(
            'http://s3.amazonaws.com/%s/%s/%s',
            $this->getBucketName(),
            $this->getPrefix(),
            $media->getFilename()
        );*/
    }

    /**
     * @return string
     * @throws \LogicException
     */
    private function getBucketName()
    {
        return $this->bucketName;
    }

    private function getPrefix()
    {
        return $this->prefix;
    }
}
