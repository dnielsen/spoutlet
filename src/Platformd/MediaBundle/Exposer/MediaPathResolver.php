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

    private $container;

    public function __construct($bucketName, $prefix, $container)
    {
        $this->bucketName = $bucketName;
        $this->prefix = $prefix == '' ?: substr($prefix, 0, 1) == "/" ? $prefix : '/'.$prefix;
        $this->container = $container;
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

        // TODO: this needs to be a config param
        if ($this->bucketName == "platformd") {
            $cf = $this->getIsHttps() ? "https://d2ssnvre2e87xh.cloudfront.net" : "http://media.alienwarearena.com";
        } else {
            $cf = $this->getIsHttps() ? "https://d3klgvi09f3c52.cloudfront.net" : "http://mediastaging.alienwarearena.com";
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

    private function getIsHttps()
    {
        if (!$this->container->isScopeActive('request')) {
            return false;
        }

        return $this->container->get('request')->getScheme() == 'https';
    }

    private function getScheme()
    {
        if (!$this->container->isScopeActive('request')) {
            return 'http';
        }

        return $this->container->get('request')->getScheme();
    }
}
