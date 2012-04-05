<?php

namespace Platformd\TranslationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Repository for querying for translations tokens
 */
class TranslationTokenRepository extends EntityRepository
{
    /**
     * Used in Updater to return all of the tokens for a domain, where the array key is the token
     *
     * @param string $domain
     * @return array
     */
    public function getTokensForDomainKeyedArray($domain)
    {
        $results = $this->createQueryBuilder('tt')
            ->andWhere('tt.domain = :domain')
            ->setParameters(array(
                'domain'   => $domain,
            ))
            ->getQuery()
            ->execute()
        ;

        $arr = array();
        foreach ($results as $result) {
            $arr[$result->getToken()] = $result;
        }

        return $arr;
    }

    /**
     * Returns an array of how many translations each locale has
     *
     * array(
     *   'en' => array('total' => 10, 'complete' => 8)
     * )
     *
     * @param array $locales
     * @return array
     */
    public function getLocalesStatusArray(array $locales)
    {
        $localesArr = array();

        foreach ($locales as $locale) {
            $result = $this->createQueryBuilder('tt')
                ->leftJoin('tt.translations', 't')
                // allows us to match on the language, but still match to null results if there is no joining record
                ->andWhere('t.language = :language OR t.language IS NULL')
                ->setParameter('language', $locale)
                ->select('tt.id as ttid, t.id as tid')
                ->getQuery()
                ->execute()
            ;

            $total = count($result);
            $totalComplete = 0;
            foreach ($result as $res) {
                if (null !== $res['tid']) {
                    $totalComplete++;
                }
            }

            $localesArr[$locale] = array('total' => $total, 'complete' => $totalComplete);
        }

        return $localesArr;
    }

    /**
     * Returns all of the "editable" translation tokens - those without a parent
     *
     * @return \Platformd\TranslationBundle\Entity\TranslationToken[]
     */
    public function findAllNonChildrenTokens()
    {
        return $this->createQueryBuilder('tt')
            ->andWhere('tt.parent IS NULL')
            ->getQuery()
            ->execute()
        ;
    }
}