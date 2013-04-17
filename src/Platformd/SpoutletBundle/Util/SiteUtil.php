<?php

namespace Platformd\SpoutletBundle\Util;

use Platformd\SpoutletBundle\Entity\Site;

use Doctrine\ORM\EntityManager;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class SiteUtil
{
    private $em;

    /**
     * @var Site
     */
    private $currentSite;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @return \Platformd\SpoutletBundle\Entity\Site
     */
    public function getCurrentSite() {
        return $this->currentSite;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $currentHost = $event->getRequest()->getHost();

        $this->currentSite = $this->em->getRepository('SpoutletBundle:Site')->findOneByFullDomain($currentHost);

        if (!$this->currentSite) {
            die("Could not find current site (domain = '".$currentHost."').");
        }
    }
}
