<?php

namespace Platformd\UserBundle;

use Platformd\SpoutletBundle\PathResolver;
use Platformd\UserBundle\Entity\UserAvatar;

/**
*
*/
class AvatarPathResolver extends PathResolver
{
  /**
   * {@inheritDoc}
   */
  public function getPath($avatar, array $options)
  {
    return parent::getPath($avatar->getMedia(), $options);
  }

  /**
   * {@inheritDoc}
   */
  public function supports($media, array $options)
  {

    return $media instanceof UserAvatar;
  }

}
