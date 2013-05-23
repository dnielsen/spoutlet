<?php

namespace Platformd\SpoutletBundle\Takeover;

use Platformd\SpoutletBundle\Util\SiteUtil;
use Doctrine\ORM\EntityManager;
use Platformd\SpoutletBundle\Entity\Site;

class SiteTakeoverManager
{
    private $em;
    private $siteUtil;


    public function __construct(EntityManager $em, SiteUtil $siteUtil)
    {
        $this->em = $em;
        $this->siteUtil = $siteUtil;

    }

    public function currentTakeoverExists()
    {
        $site = $this->siteUtil->getCurrentSite();
        $repo = $this->em->getRepository('SpoutletBundle:SiteTakeover');

        return $repo->getCurrentTakeover($site);
    }
}
