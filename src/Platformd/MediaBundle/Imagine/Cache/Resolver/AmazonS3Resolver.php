<?php

namespace Platformd\MediaBundle\Imagine\Cache\Resolver;

use Liip\ImagineBundle\Imagine\Cache\Resolver\AmazonS3Resolver as BaseAmazonS3Resolver;

use \AmazonS3;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpFoundation\Response;

use Doctrine\ORM\EntityManager;

use Platformd\MediaBundle\Entity\FilteredMedia;

class AmazonS3Resolver extends BaseAmazonS3Resolver
{
    protected $storage;
    protected $bucket;
    protected $acl;
    protected $objUrlOptions;
    protected $em;

    public function __construct(AmazonS3 $storage, $bucket, EntityManager $em, $acl = AmazonS3::ACL_PUBLIC, array $objUrlOptions = array())
    {
        $this->storage          = $storage;
        $this->bucket           = $bucket;
        $this->acl              = $acl;
        $this->objUrlOptions    = $objUrlOptions;
        $this->em               = $em;
    }

    public function store(Response $response, $targetPath, $filter)
    {
        $storageResponse = $this->storage->create_object($this->bucket, $targetPath, array(
            'body' => $response->getContent(),
            'contentType' => $response->headers->get('Content-Type'),
            'length' => strlen($response->getContent()),
            'acl' => $this->acl,
        ));

        if ($storageResponse->isOK()) {
            $response->setStatusCode(301);
            $response->headers->set('Location', $this->getObjectUrl($targetPath));

            $this->persistFilteredMedia($targetPath, $filter);

        } else {
            if ($this->logger) {
                $this->logger->warn('The object could not be created on Amazon S3.', array(
                    'targetPath' => $targetPath,
                    'filter' => $filter,
                    's3_response' => $storageResponse,
                ));
            }
        }

        return $response;
    }

    public function getBrowserPath($targetPath, $filter, $absolute = false)
    {
        $objectPath     = $this->getObjectPath($targetPath, $filter);
        $imageExistsDb  = (bool) $this->em->getRepository('MediaBundle:FilteredMedia')->findByPath($objectPath);

        if ($imageExistsDb) {
            return $this->getObjectUrl($objectPath);
        } else {

            $imageExistsOnS3 = $this->storage->if_object_exists($this->bucket, $objectPath);

            if ($imageExistsOnS3) {
                $this->persistFilteredMedia($objectPath, $filter);
                return $this->getObjectUrl($objectPath);
            }
        }

        $params = array('path' => ltrim($targetPath, '/'));

        return str_replace(
            urlencode($params['path']),
            urldecode($params['path']),
            $this->cacheManager->getRouter()->generate('_imagine_'.$filter, $params, $absolute)
        );
    }

    protected function persistFilteredMedia($targetPath, $filter)
    {
        $filteredMedia  = $this->em->getRepository('MediaBundle:FilteredMedia')->findOneByPath($targetPath);

        if (!$filteredMedia) {

            $filename       = str_replace($filter.'/', '', $targetPath);
            $parentMedia    = $this->em->getRepository('MediaBundle:Media')->findOneByFilename($filename);

            $filteredMedia = new FilteredMedia();
            $filteredMedia->setPath($targetPath);
            $filteredMedia->setParent($parentMedia);
            $this->em->persist($filteredMedia);
            $this->em->flush();
        }
    }

    protected function objectExists($objectPath)
    {
        $filteredMedia = $this->em->getRepository('MediaBundle:FilteredMedia')->findByPath($objectPath);

        if ($filteredMedia) {
            return true;
        }

        return $this->storage->if_object_exists($this->bucket, $objectPath);
    }
}
