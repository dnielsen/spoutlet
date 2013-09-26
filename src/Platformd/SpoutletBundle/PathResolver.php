<?php

namespace Platformd\SpoutletBundle;

use MediaExposer\PathResolver as BasePathResolver;
use Gaufrette\Adapter\AmazonS3;
use Gaufrette\Filesystem;

/**
 * Our generic Path resolver that gets things from Gaufrette
 *
 * The only thing that may be unique between resolvers is the exact
 * path being loaded and a potential "prefix" on the filename.
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
  public function __construct(Filesystem $filesystem, $prefix = '')
  {
    $this->filesystem = $filesystem;
    $this->prefix = $prefix == '' ?: substr($prefix, 0, 1) == "/" ? $prefix : '/'.$prefix;
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

        $cf = 'http://'.$this->bucketName.'.s3.amazonaws.com';

        return sprintf('%s%s/%s', $cf, $this->prefix, $path);
    }

    return '/uploads'.$this->prefix.'/'.$path;
  }

}
