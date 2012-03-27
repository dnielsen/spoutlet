<?php

namespace Platformd\TranslationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

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
}