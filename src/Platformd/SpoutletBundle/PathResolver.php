<?php

namespace Platformd\SpoutletBundle;

use MediaExposer\PathResolver as BasePathResolver;
use Gaufrette\Adapter\AmazonS3;
use Gaufrette\Filesystem;

/**
* 
*/
abstract class PathResolver implements BasePathResolver
{
  /**
   * @var Gaufrette\Filesystem
   */
  protected $filesystem;

  /**
   * @var string
   */
  protected $prefix;

  /**
   * @var string
   */
  protected $bucketName;


  /**
   * @param Gaufrette\Filesystem $filesystem
   */
  public function __construct(Filesystem $filesystem, $prefix)
  {
    $this->filesystem = $filesystem; 
    $this->prefix = $prefix;
  }

  /**
   * Set the bucket where to look for the file
   *
   * @param String $name
   * @param \AmazonS3 $s3
   */
  public function setBucket($name) 
  {
    $this->bucketName = $name;
  }

  public function getPath($path, array $options)
  {

    if ($this->filesystem->getAdapter() instanceof AmazonS3) {

        return sprintf('http://s3.amazonaws.com/%s/%s/%s', $this->bucketName, $this->prefix, $path);
    }

    return '/uploads/'.$this->prefix.'/'.$path;
  }

}