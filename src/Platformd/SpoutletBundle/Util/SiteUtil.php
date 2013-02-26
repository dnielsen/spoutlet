<?php

namespace Platformd\SpoutletBundle\Util;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class SiteUtil
{
    private $em;
    private $host;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getCurrentSite()
    {
        if (!$currentHost = $this->host) {
            die("OnKernelRequest has not correctly set the host. Getting current site failed.");
            return;
        }

        $subDomain = str_replace('staging', '', substr($currentHost, 0, stripos($currentHost, '.')));
        $site = $this->getSiteFromSubDomain($subDomain);

        if (!$site) {
            die("Could not find site for '".$subDomain."'.");
        }

        return $site;
    }

    public function getSiteFromSubDomain($subDomain) {
        return $this->em->getRepository('SpoutletBundle:Site')->findOneBySubDomain($subDomain);
    }

    public function getAllSites()
    {
        return $this->em->getRepository('SpoutletBundle:Site')->findAll();
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $this->host = $event->getRequest()->getHost();
    }
}
