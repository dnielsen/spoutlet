<?php

namespace Platformd\SpoutletBundle\Util;

use Platformd\SpoutletBundle\Entity\Site;
use Platformd\SpoutletBundle\Util\CacheUtil;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SiteUtil extends Event
{
    private $currentHost;
    private $currentSite = null;
    private $currentSiteCached;
    private $cachedSiteInfoArray;
    private $cacheUtil;
    private $siteRepo;
    private $eventDispatcher;

    public function __construct($siteRepo, CacheUtil $cacheUtil, EventDispatcherInterface $eventDispatcher)
    {
        $this->siteRepo        = $siteRepo;
        $this->cacheUtil       = $cacheUtil;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getCurrentSite() {

        if ($this->currentSite) {
            return $this->currentSite;
        }

        $this->currentSite = $this->getCurrentSiteFromDb();

        if (!$this->currentSite) {
            $this->abortCurrentRequest();
        }

        return $this->currentSite;
    }

    private function abortCurrentRequest() {
        die("Could not find current site (domain = '".$this->currentHost."').");
    }

    private function getCurrentSiteFromDb() {
        return $this->siteRepo->findOneByFullDomain($this->currentHost);
    }

    # do not use this function (getCurrentSiteCached - note the CACHED at the end)... it is only for high performance pages.
    # If you don't know for SURE you can use it... don't... if however you insist on using it... please be aware that this
    # cached site information comes from the cache... so it's not an entity that is managed by the doctrine entity manager
    # and as such you need to correctly merge it, while taking care not to accidentally override valid live database changes
    # with this some what stale cached version.
    public function getCurrentSiteCached() {
        return $this->currentSiteCached;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $this->currentHost = $event->getRequest()->getHost();
        $siteRepo          = $this->siteRepo;
        $currentHost       = $this->currentHost;

        $this->cachedSiteInfoArray = $this->cacheUtil->getOrGen(array(
            'key'                  => 'SITE_INFO_ARRAY_CACHE',
            'hashKey'              => false,
            'cacheDurationSeconds' => 120,
            'siteSpecific'         => false,
            'genFunction'          => function & () use (&$siteRepo, &$currentHost) {

                $sites = $siteRepo->findAll();
                $arr   = array();

                foreach ($sites as $site) {

                    if (false === strpos($site->getFullDomain(), 'migration')) {
                        $migrationDomain = str_replace('.alienwarearena', 'migration.alienwarearena', $site->getFullDomain());
                        $site->setFullDomain($migrationDomain);
                    } else {
                        $migrationDomain = $site->getFullDomain();
                    }

                    $arr[$migrationDomain] = $site;
                }

                return $arr;
            }
        ));

        if (!isset($this->cachedSiteInfoArray[$this->currentHost])) {
            $this->abortCurrentRequest();
        }

        $this->currentSiteCached = $this->cachedSiteInfoArray[$this->currentHost];

        $this->eventDispatcher->dispatch('awa.site_util.current_site_set', $this);
    }

    public function getSiteFromCountry($country) {
        return $this->em->getRepository('SpoutletBundle:Region')->findOneByCountry($country);
    }

    public function getAllSites()
    {
        return $this->em->getRepository('SpoutletBundle:Site')->findAll();
    }
}
