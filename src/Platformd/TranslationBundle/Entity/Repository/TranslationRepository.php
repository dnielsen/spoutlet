<?php

namespace Platformd\TranslationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Platformd\TranslationBundle\Entity\TranslationToken;
use Platformd\TranslationBundle\Entity\Translation;

/**
 * Repository for querying for translations
 */
class TranslationRepository extends EntityRepository
{
    public function getTranslationsForLanguageAndDomain($language, $domain)
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.translationToken', 'tt')
            ->andWhere('t.language = :language')
            ->andWhere('tt.domain = :domain')
            ->setParameters(array(
                'language' => $language,
                'domain'   => $domain,
            ))
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @param \Platformd\TranslationBundle\Entity\TranslationToken $token
     * @param $locale
     * @return \Platformd\TranslationBundle\Entity\Translation
     */
    public function getOrCreateTranslation(TranslationToken $token, $locale)
    {
        $translation = $this->createQueryBuilder('t')
            ->andWhere('t.translationToken = :token')
            ->andWhere('t.language = :language')
            ->setParameters(array(
                'token' => $token,
                'language' => $locale
            ))
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;

        if (!$translation) {
            $translation = new Translation();
            $translation->setTranslationToken($token);
            $translation->setLanguage($locale);
        }

        return $translation;
    }
}