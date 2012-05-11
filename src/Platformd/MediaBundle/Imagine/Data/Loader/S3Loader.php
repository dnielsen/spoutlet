<?php

namespace Platformd\MediaBundle\Imagine\Data\Loader;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Liip\ImagineBundle\Imagine\Data\Loader\LoaderInterface;
use Imagine\Image\ImagineInterface;
use Gaufrette\Filesystem;

/**
 * Custom loader that actually reads a thumbnail in from S3
 */
class S3Loader implements LoaderInterface
{
    /**
     * @var \Imagine\Image\ImagineInterface
     */
    protected $imagine;

    /**
     * @var \Gaufrette\Filesystem
     */
    protected $s3Filesystem;

    /**
     * @var \Liip\ImagineBundle\Imagine\Data\Loader\LoaderInterface
     */
    protected $defaultLoader;

    /**
     * @param \Imagine\Image\ImagineInterface $imagine
     * @param $thumbsDirectory
     */
    public function __construct(ImagineInterface $imagine, Filesystem $s3Filesystem, LoaderInterface $defaultLoader)
    {
        $this->imagine = $imagine;
        $this->s3Filesystem = $s3Filesystem;

        // backup loader for filesystem, temporarily
        $this->defaultLoader = $defaultLoader;
    }

    /**
     * @param string $path
     *
     * @return \Imagine\Image\ImageInterface
     */
    public function find($path)
    {
        if (false !== strpos($path, '/../') || 0 === strpos($path, '../')) {
            throw new NotFoundHttpException(sprintf("Source image was searched with '%s' out side of the defined root path", $path));
        }

        $s3Path = str_replace('uploads', '', $path);
        $s3Path = ltrim($s3Path, '/');

        // see if the file exists on S3
        if (!$this->s3Filesystem->has($s3Path)) {

            // for now, backup and try the filesystem loader
            return $this->defaultLoader->find($path);

            // later we'll remove the default loader, then throw this
            // throw new NotFoundHttpException(sprintf('Source image not found on S3 in "%s"', $relativePath));
        }

        // save the contents into a temporary file for processing
        $absolutePath = tempnam('/tmp', 'pd_s3_thumbnail');
        file_put_contents($absolutePath, $this->s3Filesystem->read($s3Path));

        $image = $this->imagine->open($absolutePath);

        // remove the temporary file
        unlink($absolutePath);

        return $image;
    }
}
