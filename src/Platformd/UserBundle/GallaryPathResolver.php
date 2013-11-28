<?php

namespace Platformd\UserBundle;

use Platformd\SpoutletBundle\PathResolver;
use Platformd\UserBundle\Entity\User;
use Platformd\UserBundle\Entity\Gallary;

class GallaryPathResolver extends PathResolver
{
  const GALLARY_BASE_URL = 'http://s3.amazonaws.com/';

  public function getPath($entity, array $options)
  {
    $defaultOptions    = array('size' => 84);
    $options           = array_merge($defaultOptions, $options);

    $filename          = $options['size'].'x'.$options['size'].'.png';
    
    if ($entity instanceof User) {
     
      $avatar    = $entity->getGallary();
      $userUuid  = $entity->getUuid();
      $userFaceImage = $entity->getFaceprintImage();
      $directory ='by_size';
    } else {
   
      $gallary    = $entity;
      $userUuid  = $entity->getUser()->getUuid();
      $directory =$entity->getUuid();
    }

    
    $gallaryUsable  = $gallary && $gallary->isUsable();

    if ($this->bucketName == "platformd") {
        $cf = "http://media.alienwarearena.com";
    } else {
      $cf = $this->hpcloud_url.$this->hpcloud_container."/images/gallary";
     //  $cf= "http://h5926e0c7296f55bd19c9ce2d388c71a3.cdn.hpcloudsvc.com";
    }

    $url = $avatarUsable ? $cf.'/'.Gallary::GALLARY_DIRECTORY_PREFIX.'/'.$userUuid.'/'.$directory.'/'.$filename : false;
    $url = $cf.'/'.$userFaceImage."_s";
       
    return $url;
  }

  public function supports($media, array $options)
  {
    return $media instanceof User || $media instanceof Gallary;
  }

}
