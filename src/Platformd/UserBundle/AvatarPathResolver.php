<?php

namespace Platformd\UserBundle;

use Platformd\SpoutletBundle\PathResolver;
use Platformd\UserBundle\Entity\User;
use Platformd\UserBundle\Entity\Avatar;

class AvatarPathResolver extends PathResolver
{
  const AVATAR_BASE_URL = 'http://s3.amazonaws.com/';

  public function getPath($entity, array $options)
  {
    $defaultOptions    = array('size' => 84);
    $options           = array_merge($defaultOptions, $options);

    $filename          = $options['size'].'x'.$options['size'].'.png';
    
    if ($entity instanceof User) {
     
      $avatar    = $entity->getAvatar();
      $userUuid  = $entity->getUuid();
      $userFaceImage = $entity->getFaceprintImage();
      $directory ='by_size';
    } else {
   
      $avatar    = $entity;
      $userUuid  = $entity->getUser()->getUuid();
      $directory =$entity->getUuid();
    }

    
    $avatarUsable  = $avatar && $avatar->isUsable();

    if ($this->bucketName == "platformd") {
        $cf = "http://media.alienwarearena.com";
    } else {
      // $cf ="https://region-a.geo-1.objects.hpcloudsvc.com:443/v1/10873218563681/cloudcamp/images/avatar";
       $cf = $this->hpcloud_url.$this->hpcloud_container."/images/avatar";
     //  $cf= "http://h5926e0c7296f55bd19c9ce2d388c71a3.cdn.hpcloudsvc.com";
    }
    if($this->objectStorage == 'HpObjectStorage') {
      $url = $avatarUsable ? $cf.'/'.Avatar::AVATAR_DIRECTORY_PREFIX.'/'.$userUuid.'/'.$directory.'/'.$filename : false;
      $url = $cf.'/'.$userFaceImage."_s";
   } 
  else {

      $url = self::AVATAR_BASE_URL.$this->bucketName."/images/avatars/".$userUuid;
   }    
    return $url;
  }

  public function getPathFromParams($userUuid, $size=84)
  {
    $filename  = $size.'x'.$size.'.png';
    $directory ='by_size';

    if ($this->bucketName == "platformd") {
        $cf = "http://media.alienwarearena.com";
    } else {
        $cf = "http://mediastaging.alienwarearena.com";
    }

    $url = $cf.'/'.Avatar::AVATAR_DIRECTORY_PREFIX.'/'.$userUuid.'/'.$directory.'/'.$filename;

    return $url;
  }

  public function supports($media, array $options)
  {
    return $media instanceof User || $media instanceof Avatar;
  }

}
