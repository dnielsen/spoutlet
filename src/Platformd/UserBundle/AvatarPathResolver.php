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

  /**
   * @var string
   */
  private $bucketName;

  /**
   * @var \AmazonS3
   */
  private $amazonS3;

  /**
   * @param Gaufrette\Filesystem $filesystem
   */
  public function __construct(Filesystem $filesystem)
  {
    $this->filesystem = $filesystem; 
  }

  /**
   * {@inheritDoc}
   */
  public function getPath($user, array $options)
  {
    if ($this->filesystem->getAdapter() instanceof AmazonS3 && $this->amazonS3) {
      
      return $this->amazonS3->get_object_url($this->bucketName, $user->getAvatar());
    }

    return '/uploads/avatars/'.$user->getAvatar();
  }

  /**
   * Set the bucket where to look for the file
   *
   * @param String $name
   * @param \AmazonS3 $s3
   */
  public function setBucket($name, \AmazonS3 $s3) 
  {
    $this->bucketName = $name;
    $this->amazonS3 = $s3;
  }

  /**
   * {@inheritDoc}
   */
  public function supports($media, array $options)
  {
   
    return $media instanceof User;
  }

}