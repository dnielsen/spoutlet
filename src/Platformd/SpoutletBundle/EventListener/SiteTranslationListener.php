<?php

namespace Platformd\SpoutletBundle\EventListener;
use Platformd\SpoutletBundle\Util\SiteUtil;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

class SiteTranslationListener
{

    private $siteUtil;
    private $logger;

    public function __construct(SiteUtil $siteUtil) {
        $this->siteUtil = $siteUtil;
    }

    # this automatically sets the session's locale so that symfony's built in translations can work correctly
    # given that the database now stores the default locale per site.
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $siteLocale    = $this->siteUtil->getCurrentSite()->getDefaultLocale();
        $session       = $event->getRequest()->getSession();
        $sessionLocale = $session->getLocale();

        if ($siteLocale === $sessionLocale) {
            $this->logger->info("Session locale already correctly set to '".$siteLocale."'.");
            return;
        }

        $session->setLocale($siteLocale);
        $session->save();

        $this->logger->info("Session locale was set to '".$sessionLocale."' but it has now been correctly updated to be '".$siteLocale."'.");
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
