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
        $cf = "http://mediastaging.alienwarearena.com";
    }

    $url = $avatarUsable ? $cf.'/'.Avatar::AVATAR_EXTERNAL_DIRECTORY_PREFIX.'/'.$userUuid.'/'.$directory.'/'.$filename : false;

    return $url;
  }

  public function supports($media, array $options)
  {
    return $media instanceof User || $media instanceof Avatar;
  }

}
