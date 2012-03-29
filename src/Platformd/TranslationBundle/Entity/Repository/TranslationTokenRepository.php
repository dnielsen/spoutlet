<?php

namespace Platformd\TranslationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Repository for querying for translations tokens
 */
class TranslationTokenRepository extends EntityRepository
{
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
}