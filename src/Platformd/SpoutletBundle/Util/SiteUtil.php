<?php

namespace Platformd\SpoutletBundle\Util;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SiteUtil
{
    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function getCurrentSite()
    {
        $currentHost    = $this->container->get('request')->getHost();
        $subDomain      = str_replace('staging', '', substr($currentHost, 0, stripos($currentHost, '.')));

        return $this->getSiteFromSubDomain($subDomain);
    }

    public function getSiteFromSubDomain($subDomain) {
        return $this->em->getRepository('SpoutletBundle:Site')->findOneBySubDomain($subDomain);
    }

    public function getAllSites()
    {
        return $this->em->getRepository('SpoutletBundle:Site')->findAll();
    }
}
