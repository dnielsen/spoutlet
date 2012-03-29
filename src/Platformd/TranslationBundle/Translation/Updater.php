<?php

namespace Platformd\TranslationBundle\Translation;

use JMS\TranslationBundle\Translation\Config;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Translation\LoaderManager;
use JMS\TranslationBundle\Translation\ExtractorManager;
use Doctrine\ORM\EntityManager;
use JMS\TranslationBundle\Model\MessageCollection;
use Platformd\TranslationBundle\Entity\TranslationToken;

/**
 * Responsible for collecting the "scanned" catalog and then making changes to our TranslationToken database
 *
 * Built off of the Updater inside JMSTranslationBundle
 */
class Updater
{
    /**
     * @var \JMS\TranslationBundle\Translation\Config
     */
    private $config;

    /**
     * @var \Symfony\Component\HttpKernel\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \JMS\TranslationBundle\Translation\ExtractorManager
     */
    private $extractor;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \JMS\TranslationBundle\Translation\LoaderManager
     */
    private $loader;

    /**
     * @var \JMS\TranslationBundle\Model\MessageCatalogue
     */
    private $existingCatalogue;

    /**
     * @var \JMS\TranslationBundle\Model\MessageCatalogue
     */
    private $scannedCatalogue;

    public function __construct(LoaderManager $loader, ExtractorManager $extractor, EntityManager $em, LoggerInterface $logger)
    {
        $this->loader = $loader;
        $this->extractor = $extractor;
        $this->em = $em;
        $this->logger = $logger;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function updateTranslationTokens()
    {
        if (!$this->scannedCatalogue) {
            throw new \LogicException('You must call setConfig before calling updateTranslationTokens');
        }

        foreach ($this->scannedCatalogue->getDomains() as $domain => $messageCollection) {
            $this->updateTranslationsTokensForDomain($domain, $messageCollection);
        }
    }

    /**
     * Updates a specific domain of TranslationToken
     *
     * @param $domain
     * @param \JMS\TranslationBundle\Model\MessageCollection $messageCollection
     */
    private function updateTranslationsTokensForDomain($domain, MessageCollection $messageCollection)
    {
        $existingTokens = $this->getTranslationTokenRepo()->getTokensForDomainKeyedArray($domain);

        foreach ($messageCollection->all() as $message)
        {
            if (!isset($existingTokens[$message->getId()])) {
                $newToken = new TranslationToken();
                $newToken->setDomain($domain);
                $newToken->setToken($message->getId());
                $newToken->setIsFromExtraction(true);

                $this->em->persist($newToken);

                $this->logger->info(sprintf('Adding new token: "%s" into domain "%s"', $message->getId(), $domain));
            } else {
                // existing token
                $existingToken = $existingTokens[$message->getId()];
                unset($existingTokens[$message->getId()]);

                $existingToken->setIsFromExtraction(true);

                $this->em->persist($existingToken);
            }
        }

        // iterate through the existing tokens that were not found, mark them as such
        foreach ($existingTokens as $existingToken) {
            $existingToken->setIsFromExtraction(false);

            $this->em->persist($existingToken);
            $this->logger->info(sprintf('Marking existing token "%s" as unused', $existingToken->getToken()));
        }

        //$this->em->flush();
    }

    /**
     * @return \Platformd\TranslationBundle\Entity\Repository\TranslationTokenRepository
     */
    private function getTranslationTokenRepo()
    {
        return $this->em->getRepository('TranslationBundle:TranslationToken');
    }

    /**
     * @return \JMS\TranslationBundle\Model\MessageCatalogue
     */
    public function getScannedCatalogue()
    {
        return $this->scannedCatalogue;
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