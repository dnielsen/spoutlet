<?php

namespace Platformd\SpoutletBundle\Tenant;

use Doctrine\ORM\EntityRepository;

/**
 * Manager for multitenancy sites
 */
class SiteManager
{
    private $repo;

    function __construct(EntityRepository $repo) {
        $this->repo = $repo;
    }

    public function getSiteChoices()
    {
        $sites = $this->repo->findAll();
        $siteChoices = array();

        foreach ($sites as $site) {
            $siteChoices[$site->getId()]['name'] = $site->getName();
            $siteChoices[$site->getId()]['fullDomain'] = $site->getFullDomain();
            $siteChoices[$site->getId()]['defaultLocale'] = $site->getDefaultLocale();
        }

        return $siteChoices;
    }

    public function getSiteName($id)
    {
        $site = $this->repo->find($id);
        return $site ? $site->getName() : null;
    }
}
