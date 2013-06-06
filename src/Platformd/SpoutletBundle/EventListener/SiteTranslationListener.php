<?php

namespace Platformd\SpoutletBundle\EventListener;
use Platformd\SpoutletBundle\Util\SiteUtil;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session;

class SiteTranslationListener
{
    const LOG_MESSAGE_PREFIX = '[SiteTranslationListener] %s';

    private $logger;
    private $currentSiteLocale;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    private function logInfo($message) {
        $this->logger->info(sprintf(self::LOG_MESSAGE_PREFIX, $message));
    }

    private function logAlertAndDie($message) {
        $message = sprintf(self::LOG_MESSAGE_PREFIX, $message);
        $this->logger->alert($message);
        die($message);
    }

    private function logAlert($message) {
        $this->logger->alert(sprintf(self::LOG_MESSAGE_PREFIX, $message));
    }

    private function logDebug($message) {
        $this->logger->debug(sprintf(self::LOG_MESSAGE_PREFIX, $message));
    }

    public function setCurrentLocale(SiteUtil $siteUtil) {
        $this->currentSiteLocale = $siteUtil->getCurrentSiteCached()->getDefaultLocale();
        $this->logDebug('currentSiteLocale now set to "'.$this->currentSiteLocale.'".');
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        if (!$this->currentSiteLocale) {
            $this->logAlertAndDie("onKernelRequest happened but currentSiteLocale has not been set yet.");
        }

        $session       = $event->getRequest()->getSession();
        $sessionLocale = $session->getLocale();

        if ($this->currentSiteLocale === $sessionLocale) {
            $this->logInfo('Session locale already correctly set to "'.$this->currentSiteLocale.'".');
            return;
        }

        $session->setLocale($this->currentSiteLocale);
        $session->save();

        $this->logInfo('Session locale was set to "'.$sessionLocale.'" but it has now been correctly updated to be "'.$this->currentSiteLocale.'".');
    }
}
