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
        $this->prefix = $prefix == '' ?: substr($prefix, 0, 1) == "/" ? $prefix : '/'.$prefix;
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

        // used when you're going to pipe it into imagine
        if (isset($options['local']) && $options['local']) {
            return $media->getFilename();
        }

        if ($this->bucketName == "platformd") {
            $cf = "http://media.alienwarearena.com";
        } else {
            $cf = "http://mediastaging.alienwarearena.com";
        }

        return sprintf('%s%s/%s', $cf, $this->prefix, $media->getFilename());
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
