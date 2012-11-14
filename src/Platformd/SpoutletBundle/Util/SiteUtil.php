<?php

namespace Platformd\SpoutletBundle\Util;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;

class SiteUtil
{
    public function __construct(EntityManager $em, Request $request)
    {
        $this->em = $em;
        $this->request = $request;
    }

    public function getCurrentSite()
    {
        $currentHost    = $this->request->getHost();
        $subDomain      = str_replace('staging', '', substr($currentHost, 0, stripos($currentHost, '.')));

        return $this->getSiteFromSubDomain($subDomain);
    }

    public function getSiteFromSubDomain($subDomain) {
        return $this->em->getRepository('SpoutletBundle:Site')->findOneBySubDomain($subDomain);
    }
}
