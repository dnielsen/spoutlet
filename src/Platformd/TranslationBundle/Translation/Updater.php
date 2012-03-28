<?php

namespace Platformd\TranslationBundle\Translation;

use JMS\TranslationBundle\Translation\Config;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use JMS\TranslationBundle\Model\MessageCatalogue;

/**
 * Responsible for collecting the "scanned" catalog and then making changes to our TranslationToken database
 *
 * Built off of the Updater inside JMSTranslationBundle
 */
class Updater
{
    private $config;

    /**
     * @var \Symfony\Component\HttpKernel\Log\LoggerInterface
     */
    private $logger;

    private $existingCatalogue;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param Config $config
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;

        $this->logger->info(sprintf("Loading catalogues from \"%s\"", $config->getTranslationsDir()));
        $this->existingCatalogue = new MessageCatalogue();

        // load external resources, so current translations can be reused in the final translation
        foreach ($config->getLoadResources() as $resource) {
            $this->existingCatalogue->merge($this->loader->loadFromDirectory(
                $resource,
                $config->getLocale()
            ));
        }

        $this->existingCatalogue->merge($this->loader->loadFromDirectory(
            $config->getTranslationsDir(), $config->getLocale()
        ));

        $this->extractor->setDirectories($config->getScanDirs());
        $this->extractor->setExcludedDirs($config->getExcludedDirs());
        $this->extractor->setExcludedNames($config->getExcludedNames());
        $this->extractor->setEnabledExtractors($config->getEnabledExtractors());

        $this->logger->info("Extracting translation keys");
        $this->scannedCatalogue = $this->extractor->extract();
        $this->scannedCatalogue->setLocale($config->getLocale());

        // merge existing messages into scanned messages
        foreach ($this->scannedCatalogue->getDomains() as $domainCatalogue) {
            foreach ($domainCatalogue->all() as $message) {
                if (!$this->existingCatalogue->has($message)) {
                    continue;
                }

                $message->mergeExisting($this->existingCatalogue->get($message->getId(), $message->getDomain()));
            }
        }

        if ($this->config->isKeepOldMessages()) {
            foreach ($this->existingCatalogue->getDomains() as $domainCatalogue) {
                foreach ($domainCatalogue->all() as $message) {
                    if ($this->scannedCatalogue->has($message)) {
                        continue;
                    }

                    $this->scannedCatalogue->add($message);
                }
            }
        }
    }
}