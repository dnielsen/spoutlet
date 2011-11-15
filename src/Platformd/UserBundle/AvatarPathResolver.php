<?php

namespace Platformd\Userbundle;

use MediaExposer\PathResolver;
use Platformd\UserBundle\Entity\User;

/**
* 
*/
class AvatarPathResolver implements PathResolver
{

  public function getPath($user, array $options)
  {
    return '/uploads/avatars/'.$user->getAvatar();
  }

  public function supports($media, array $options)
  {
    return $media instanceof User;
  }

}