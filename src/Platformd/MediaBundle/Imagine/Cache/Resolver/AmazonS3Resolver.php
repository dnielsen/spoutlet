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
    protected $container;

    public function __construct(AmazonS3 $storage, $bucket, EntityManager $em, $container, $acl = AmazonS3::ACL_PUBLIC, array $objUrlOptions = array())
    {
        $this->storage          = $storage;
        $this->bucket           = $bucket;
        $this->acl              = $acl;
        $this->objUrlOptions    = $objUrlOptions;
        $this->em               = $em;
        $this->container        = $container;
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

    protected function getObjectUrl($targetPath)
    {
        if ($this->bucket == "platformd") {
            $cf = "media.alienwarearena.com";
        } else {
            $cf = "mediastaging.alienwarearena.com";
        }

        $url        = $this->storage->get_object_url($this->bucket, $targetPath, 0, $this->objUrlOptions);
        $parts      = parse_url($url);
        $urlHost    = $parts['host'];

        return str_replace($urlHost, $cf, $url);
    }

    public function getBrowserObjectUrl($targetPath)
    {
        if ($this->bucket == "platformd") {
            $cf = $this->getIsHttps() ? "https://d2ssnvre2e87xh.cloudfront.net" : "media.alienwarearena.com";
        } else {
            $cf = $this->getIsHttps() ? "d3klgvi09f3c52.cloudfront.net" : "mediastaging.alienwarearena.com";
        }

        $url        = $this->storage->get_object_url($this->bucket, $targetPath, 0, $this->objUrlOptions);
        $parts      = parse_url($url);
        $urlHost    = $parts['host'];
        $scheme    = $parts['scheme'];

        return str_replace(array($urlHost, $scheme), array($cf, $this->getScheme()), $url);
    }

    public function getBrowserPath($targetPath, $filter, $absolute = false)
    {
        $objectPath     = $this->getObjectPath($targetPath, $filter);
        $imageExistsDb  = (bool) $this->em->getRepository('MediaBundle:FilteredMedia')->findByPath($objectPath);

        if ($imageExistsDb) {
            return $this->getBrowserObjectUrl($objectPath);
        } else {

            $imageExistsOnS3 = $this->storage->if_object_exists($this->bucket, $objectPath);

            if ($imageExistsOnS3) {
                $this->persistFilteredMedia($objectPath, $filter);
                return $this->getBrowserObjectUrl($objectPath);
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

    private function getIsHttps()
    {
        if (!$this->container->isScopeActive('request')) {
            return false;
        }

        return $this->container->get('request')->getScheme() == 'https';
    }

    private function getScheme()
    {
        if (!$this->container->isScopeActive('request')) {
            return 'http';
        }

        return $this->container->get('request')->getScheme();
    }
}
