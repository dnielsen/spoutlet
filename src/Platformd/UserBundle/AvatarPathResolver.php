<?php

namespace Platformd\Userbundle;

use MediaExposer\PathResolver;

use Platformd\UserBundle\Entity\User;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\AmazonS3;

/**
* 
*/
class AvatarPathResolver implements PathResolver
{
  /**
   * @var Gaufrette\Filesystem
   */
  private $filesystem;

  public function __construct(Filesystem $filesystem)
  {
    $this->filesystem = $filesystem; 
  }

  public function getPath($user, array $options)
  {
    if ($this->filesystem->getAdapter() instanceof AmazonS3) {
      
      return 'https://s3.amazonaws.com/platformd-testing/'.$user->getAvatar(); 
      //2e7ee12be388fe7e2d0c9d8514c60b7c385eb8af.jpg';
    }

    return '/uploads/avatars/'.$user->getAvatar();
  }

  public function supports($media, array $options)
  {
    return $media instanceof User;
  }

}