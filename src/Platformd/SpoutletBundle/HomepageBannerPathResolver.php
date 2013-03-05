<?php

namespace Platformd\SpoutletBundle;

use Platformd\SpoutletBundle\PathResolver;
use Platformd\SpoutletBundle\Entity\HomepageBanner;

use Gaufrette\Adapter\AmazonS3;

/**
*
*/
class HomepageBannerPathResolver extends PathResolver
{

  /**
   * {@inheritDoc}
   */
  public function getPath($banner, array $options)
  {
    $path = isset($options['size']) && $options['size'] === 'banner' ? $banner->getBanner() : $banner->getThumb();
    if ($this->filesystem->getAdapter() instanceof AmazonS3) {

        if ($this->bucketName == "platformd") {
            $cf = "http://media.alienwarearena.com";
        } else {
            $cf = "http://mediastaging.alienwarearena.com";
        }

        return sprintf('%s%s/%s', $cf, $this->prefix, $path);
    }

    return '/uploads/'.$this->prefix.'/'.$path;
  }

  /**
   * {@inheritDoc}
   */
  public function supports($media, array $options)
  {

    return $media instanceof HomepageBanner;
  }
}
