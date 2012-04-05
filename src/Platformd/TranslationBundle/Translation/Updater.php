<?php

namespace Platformd\TranslationBundle\Translation;

use JMS\TranslationBundle\Translation\Config;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use JMS\TranslationBundle\Model\MessageCatalogue;
use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManager;
use JMS\TranslationBundle\Model\MessageCollection;
use Platformd\TranslationBundle\Entity\TranslationToken;
use Symfony\Component\Yaml\Yaml;

/**
 * Responsible for collecting the "scanned" catalog and then making changes to our TranslationToken database
 *
 * Built off of the Updater inside JMSTranslationBundle
 */
class Updater
{
    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    private $translator;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    private $translationsCacheDir;

    private $kernelRootDir;

    private $metadataData;

    public function __construct(TranslatorInterface $translator, EntityManager $em, $translationsCacheDir, $kernelRootDir, LoggerInterface $logger)
    {
        $this->translator = $translator;
        $this->em = $em;
        $this->translationsCacheDir = $translationsCacheDir;
        $this->kernelRootDir = $kernelRootDir;
        $this->logger = $logger;

        //'%kernel.cache_dir%/translations';
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function updateTranslationTokens()
    {
        // prime the cache
        $this->translator->trans('dummy');

        // find the en translation file
        $cacheFile = $this->translationsCacheDir.'/catalogue.en.php';
        /** @var $messageCatalog \Symfony\Component\Translation\MessageCatalogue */
        $messageCatalog = include $cacheFile;

        foreach ($messageCatalog->getDomains() as $domain) {
            $this->updateTranslationsTokensForDomain($domain, $messageCatalog->all($domain));
        }
    }

    /**
     * Updates a specific domain of TranslationToken
     *
     * @param $domain
     * @param \JMS\TranslationBundle\Model\MessageCollection $messages
     */
    private function updateTranslationsTokensForDomain($domain, $messages)
    {
        $existingTokens = $this->getTranslationTokenRepo()->getTokensForDomainKeyedArray($domain);

        foreach ($messages as $messageKey => $message)
        {
            if (!isset($existingTokens[$messageKey])) {
                $token = new TranslationToken();
                $token->setDomain($domain);
                $token->setToken($messageKey);
                $token->setIsInMessagesFile(true);

                $this->em->persist($token);

                $this->logger->info(sprintf('Adding new token: "%s" into domain "%s"', $messageKey, $domain));
            } else {
                // existing token
                $token = $existingTokens[$messageKey];
                unset($existingTokens[$messageKey]);

                $token->setIsInMessagesFile(true);

                $this->em->persist($token);
            }

            $metadata = $this->getMetadataForKey($messageKey);
            if (isset($metadata['description']) && $metadata['description'] && !$token->getDescription()) {
                $this->logger->info(sprintf('Description for token : "%s"', $messageKey));
                $token->setDescription($metadata['description']);
            }
        }

        // iterate through the existing tokens that were not found, mark them as such
        foreach ($existingTokens as $existingToken) {
            $existingToken->setIsInMessagesFile(false);

            $this->em->persist($existingToken);
            $this->logger->info(sprintf('Marking existing token "%s" as unused', $existingToken->getToken()));
        }

        $this->em->flush();
    }

    /**
     * @param string $key The translation key
     * @return array
     */
    private function getMetadataForKey($key)
    {
        if ($this->metadataData === null) {
            $this->loadMetadata();
        }

        return isset($this->metadataData[$key]) ? $this->metadataData[$key] : array('description' => '');
    }

    /**
     * Parses the metadata.yml file and loads it into our metadata array
     *
     * @throws \Exception
     */
    private function loadMetadata()
    {
        $metadataFile = $this->kernelRootDir.'/Resources/translations/metadata.yml';

        if (!file_exists($metadataFile)) {
            throw new \Exception('Cannot find translation metadata file at '.$metadataFile);
        }

        $this->metadataData = Yaml::parse(file_get_contents($metadataFile));
    }

    /**
     * @return \Platformd\TranslationBundle\Entity\Repository\TranslationTokenRepository
     */
    private function getTranslationTokenRepo()
    {
        return $this->em->getRepository('TranslationBundle:TranslationToken');
    }
}