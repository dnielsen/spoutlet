<?php

namespace Platformd\SpoutletBundle;

use Platformd\SpoutletBundle\PathResolver;
use Platformd\SpoutletBundle\Entity\AbstractEvent;

/**
* 
*/
class BannerPathResolver extends PathResolver
{

  /**
   * {@inheritDoc}
   */
  public function getPath($event, array $options)
  {
    $prefix = isset($options['size']) ? $options['size'] : $this->prefix;
    if ($this->filesystem->getAdapter() instanceof AmazonS3) {

        return sprintf('http://s3.amazonaws.com/%s/%s/%s', $this->bucketName, $prefix, $path);
    }

    return '/uploads/'.$prefix.'/'.$path;
  }

  /**
   * {@inheritDoc}
   */
  public function supports($media, array $options)
  {
   
    return $media instanceof AbstractEvent;
  }

}