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
use Platformd\TranslationBundle\Translation\TranslationMetadata;

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

    /**
     * @var \Platformd\TranslationBundle\Translation\TranslationMetadata[]
     */
    private $metadatas = array();

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

    /**
     * Used by our Symfony Command - this adds/updates any TranslationToken
     * objects in the database based on the currently compiled english catalog.
     */
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
     * Actually updates a translation
     *
     * @param \Platformd\TranslationBundle\Entity\TranslationToken $token
     * @param string $locale The locale being updated
     * @param string $updatedTranslationString The actual raw translation
     * @param bool $withFlush
     */
    public function updateTranslation(TranslationToken $token, $locale, $updatedTranslationString, $withFlush = true)
    {
        $translation = $this->getTranslationRepository()->getOrCreateTranslation($token, $locale);
        $translation->setTranslation($updatedTranslationString);

        $this->em->persist($translation);

        // recursively update the children, but not with a flush
        foreach ($token->getChildren() as $childToken) {
            $this->updateTranslation($childToken, $locale, $updatedTranslationString, false);
        }

        if ($withFlush) {
            $this->em->flush();
        }
    }

    /**
     * Updates a specific domain of TranslationToken
     *
     * @param $domain
     * @param array $messages
     * @throws \InvalidArgumentException
     */
    private function updateTranslationsTokensForDomain($domain, array $messages)
    {
        $existingTokens = $this->getTranslationTokenRepo()->getTokensForDomainKeyedArray($domain);

        foreach ($messages as $messageKey => $message)
        {
            $metadata = $this->getMetadataForKey($messageKey);

            if (!isset($existingTokens[$messageKey])) {
                if (!$metadata->getIsEnabled()) {
                    $this->logger->info(sprintf('Skipping disabled token "%s"', $messageKey));
                    continue;
                }

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

            // set the description
            if ($metadata->getDescription() && !$token->getDescription()) {
                $this->logger->info(sprintf('Description for token : "%s"', $messageKey));
                $token->setDescription($metadata->getDescription());
            }

            // set or clear the parent
            if ($metadata->getParentTranslationKey() && $metadata->getParentTranslationKey() != $token->getParentToken()) {
                $this->logger->info(sprintf('Parent: setting "%s" to have "%s" as a parent', $messageKey, $metadata->getParentTranslationKey()));

                $parent = $this->findTokenEntity($metadata->getParentTranslationKey());
                if (!$parent) {
                    // this could be a legit, if the parent is also a new key, and the new key hasn't been inserted yet
                    $this->logger->err(sprintf(
                        'Cannot find parent with token "%s". This may be an issue of "ordering", try re-running the task to see if things are corrected',
                        $metadata->getParentTranslationKey()
                    ));
                }

                $token->setParent($parent);
            } elseif (!$metadata->getParentTranslationKey() && $token->getParentToken()) {
                $this->logger->info(sprintf('Parent: removing parent from "%s"', $messageKey));

                $token->setParent(null);
            }

            // mark as disabled if necessary
            if (!$metadata->getIsEnabled()) {
                $this->logger->info(sprintf('Deleting: removing "%s" because it is disabled', $messageKey));
                $this->em->remove($token);
            }
        }

        // iterate through the existing tokens that were not found, mark them as such
        foreach ($existingTokens as $existingToken) {
            // check to see if it's already set as not in the messages file
            if (!$existingToken->getIsInMessagesFile()) {
                continue;
            }

            $existingToken->setIsInMessagesFile(false);

            $this->em->persist($existingToken);
            $this->logger->info(sprintf('Marking existing token "%s" as unused', $existingToken->getToken()));
        }

        $this->em->flush();
    }

    /**
     * @param string $token
     * @return \Platformd\TranslationBundle\Entity\TranslationToken
     */
    private function findTokenEntity($token)
    {
        return $this->em->getRepository('TranslationBundle:TranslationToken')
            ->findOneBy(array('token' => $token))
        ;
    }

    /**
     * @param string $key The translation key
     * @return TranslationMetadata
     */
    private function getMetadataForKey($key)
    {
        if ($this->metadataData === null) {
            $this->loadMetadata();
        }

        if (!isset($this->metadatas[$key])) {
            // create the new TranslationMetadata from the array information
            $data = isset($this->metadataData[$key]) ? $this->metadataData[$key] : array();

            $metadata = new TranslationMetadata($key);

            if (isset($data['description'])) {
                $metadata->setDescription($data['description']);
            }

            if (isset($data['parent'])) {
                $metadata->setParentTranslationKey($data['parent']);
            }

            if (isset($data['disabled'])) {
                $metadata->setIsEnabled(false);
            }

            $this->metadatas[$key] = $metadata;
        }

        return $this->metadatas[$key];
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

    /**
     * @return \Platformd\TranslationBundle\Entity\Repository\TranslationRepository
     */
    private function getTranslationRepository()
    {
        return $this->em->getRepository('TranslationBundle:Translation');
    }
}