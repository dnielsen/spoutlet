<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;

class CountryRepository extends EntityRepository
{

    public function getUserAssignedCodes(User $user)
    {
        return $this
            ->createQueryBuilder('c')
            ->leftJoin('k.pool','gkp')
            ->andWhere('k.user = :user')
            ->setParameters(array('user' => $user))
            ->getQuery()
            ->execute();
    }

    public function getCountryFromCode($code)
    {
        return $this->createQueryBuilder('c')
            ->where('c.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getSingleResult();
    }

    public function getCountryCodeLocaleArray()
    {
        $result = $this
            ->createQueryBuilder('c')
            ->select('c.code, s.defaultLocale')
            ->leftJoin('c.regions','r')
            ->leftJoin('r.site','s')
            ->getQuery()
            ->execute();

        $map = array();

        foreach ($result as $countryData) {
            $map[$countryData['code']] = $countryData['defaultLocale'];
        }

        return $map;
    }
}
