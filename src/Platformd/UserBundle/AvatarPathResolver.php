<?php

namespace Platformd\Userbundle;

use Platformd\SpoutletBundle\PathResolver;
use Platformd\UserBundle\Entity\User;

/**
* 
*/
class AvatarPathResolver extends PathResolver
{

  /**
   * {@inheritDoc}
   */
  public function getPath($user, array $options)
  {

    return parent::getPath($user->getAvatar(), $options);
  }

  /**
   * {@inheritDoc}
   */
  public function supports($media, array $options)
  {
   
    return $media instanceof User;
  }

}