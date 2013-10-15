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
     //    $this->customfn();	 
    
    $path = isset($options['size']) && $options['size'] === 'banner' ? $banner->getBanner() : $banner->getThumb();
   
    if ($this->filesystem->getAdapter() instanceof AmazonS3) {
      
        if ($this->bucketName == 'platformd') {
            $cf = "http://media.alienwarearena.com";
        } else {
	    if($this->objectStorage == "HpObjectStorage")
              $cf = "https://region-a.geo-1.objects.hpcloudsvc.com/v1/10873218563681/platformd-public";
            else
              $cf = "https://s3.amazonaws.com/platformd-public";
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
