<?php

namespace Platformd\TranslationBundle\Translation\Loader;

use Symfony\Component\Translation\Loader\LoaderInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\MessageCatalogue;

class EntityLoader implements LoaderInterface
{
    /**
     * @var \Platformd\TranslationBundle\Entity\Repository\TranslationRepository
     */
    private $translationRepository;

    /**
     * @param \Doctrine\ORM\EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->translationRepository = $entityManager->getRepository("TranslationBundle:Translation");
    }

    /**
     * Loads translations from the Translation entity that correspond with the given domain and locale
     *
     * @param string $resource This is meaningless in this scenario
     * @param $locale
     * @param string $domain
     * @return \Symfony\Component\Translation\MessageCatalogue
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        try {
            $translations = $this->translationRepository->getTranslationsForLanguageAndDomain($locale, $domain);

            $catalogue = new MessageCatalogue($locale);

            /** @var $translation \Platformd\TranslationBundle\Entity\Translation */
            foreach($translations as $translation){
                $catalogue->set($translation->getTranslationToken()->getToken(), $translation->getTranslation(), $domain);
            }

            return $catalogue;
        } catch (\PDOException $e) {
            // we're extra careful here since this is during the cache warmup process
            // without this, we can really deploy new db changes to the translations without
            // everything exploding

            return new MessageCatalogue($locale);
        }
    }
}