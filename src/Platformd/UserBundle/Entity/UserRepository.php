<?php

namespace Platformd\UserBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use DateTime;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends EntityRepository
{

	/**
	 * get users that signed for a specificed giveaway key
	 *
	 * @param $giveaway_pool = giveaway_key.pool
	 * @param $site = 'en'
	 */
	public function findAssignedToUser($giveaway_pool, $site)
	{
      $qb =  $this->createSiteQueryBuilder($site)
       // ->select('u.id, u.firstname, u.lastname, u.email, u.system_tag, k.ipAddress, k.assignedAt')
        ->select('u.id, u.firstname, u.lastname, u.email, k.ipAddress, k.assignedAt')
    	->leftJoin('u.giveawayKeys', 'k')
    	->andWhere('u.id = k.user')
    	->andWhere('k.pool = :pool_id')
    	->setParameters(array(
    			'pool_id'  => $giveaway_pool,
    	))
    	->getQuery()
    	->getResult();

      return  $qb ;
	}

    public function findUserListByEmail($users)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.emailCanonical IN (:users)')
            ->setParameter('users', $users)
            ->getQuery()
            ->execute();
        ;
    }

	public function getTotalUsersForSite($site)
    {
        return $this->createSiteQueryBuilder($site)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR)
        ;
    }

    public function getTotalUsersForCountry($country)
    {
        return $this->createCountryQueryBuilder($country)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR)
        ;
    }

    public function getTotalUsersForCountries($countries)
    {
        return $this->createCountriesQueryBuilder($countries)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR)
        ;
    }

    public function getArenaOptInForAllCountries($from=null, $to=null)
    {
        $qb = $this->createQueryBuilder('u');

        if ($from || $to) {
            $qb = $this->addBetweenQuery($qb, $from, $to);
        }

        return $this->addArenaOptQuery($qb, true)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR)
        ;
    }

    public function getDellOptInForAllCountries($from=null, $to=null)
    {
        $qb = $this->createQueryBuilder('u');

        if ($from || $to) {
            $qb = $this->addBetweenQuery($qb, $from, $to);
        }

        return $this->addDellOptQuery($qb, true)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR)
        ;
    }

    public function countNewRegistrantsForAllCountries($from, $to)
    {
        $qb = $this->createQueryBuilder('u');

        return $this->addBetweenQuery($qb, $from, $to)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR)
        ;
    }


    public function getArenaOptInForSite($site)
    {
        $qb = $this->createSiteQueryBuilder($site);

        return $this->addArenaOptQuery($qb, true)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR)
        ;
    }

    public function getArenaOptInForCountry($country, $from=null, $to=null)
    {
        $qb = $this->createCountryQueryBuilder($country);

        if ($from || $to) {
            $qb = $this->addBetweenQuery($qb, $from, $to);
        }

        return $this->addArenaOptQuery($qb, true)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR)
        ;
    }

    public function getArenaOptInForCountries($countries, $from=null, $to=null)
    {
        $qb = $this->createCountriesQueryBuilder($countries);

        if ($from || $to) {
            $qb = $this->addBetweenQuery($qb, $from, $to);
        }

        return $this->addArenaOptQuery($qb, true)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR)
        ;
    }

    public function getDellOptInForSite($site)
    {
        $qb = $this->createSiteQueryBuilder($site);

        return $this->addDellOptQuery($qb, true)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR)
        ;
    }

    public function getDellOptInForCountry($country, $from=null, $to=null)
    {
        $qb = $this->createCountryQueryBuilder($country);

        if ($from || $to) {
            $qb = $this->addBetweenQuery($qb, $from, $to);
        }

        return $this->addDellOptQuery($qb, true)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR)
        ;
    }

    public function getDellOptInForCountries($countries, $from=null, $to=null)
    {
        $qb = $this->createCountriesQueryBuilder($countries);

        if ($from || $to) {
            $qb = $this->addBetweenQuery($qb, $from, $to);
        }

        return $this->addDellOptQuery($qb, true)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR)
        ;
    }

    /**
     * Returns the number of new users since the given DateTime
     *
     * @param \DateTime $since
     * @return integer
     */
    public function countNewRegistrants(DateTime $since = null, $site)
    {
        $qb = $this->createSiteQueryBuilder($site);

        return $this->addSinceQuery($qb, $since)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR)
        ;
    }

    public function countNewRegistrantsForCountry($country, $from, $to)
    {
        $qb = $this->createCountryQueryBuilder($country);

        return $this->addBetweenQuery($qb, $from, $to)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR)
        ;
    }

    public function countNewRegistrantsForCountries($countries, $from, $to)
    {
        $qb = $this->createCountriesQueryBuilder($countries);

        return $this->addBetweenQuery($qb, $from, $to)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR)
        ;
    }

    public function countOtherExpiredUsersByIpAddress($ipAddress, $username)
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.ipAddress = :ipAddress')
            ->andWhere('u.username != :username')
            ->andWhere('u.expiresAt > :now')
            ->setParameter('ipAddress', $ipAddress)
            ->setParameter('username', $username)
            ->setParameter('now', new \DateTime)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
    * @param \Doctrine\ORM\QueryBuilder $qb
    * @param $since
    * @return \Doctrine\ORM\QueryBuilder
    */
    private function addSinceQuery(QueryBuilder $qb, $since)
    {
        if ($since != null) {
            $qb->andWhere('u.created >= :since')
            ->setParameter('since', $since);
        }
        return $qb;
    }

    private function addBetweenQuery(QueryBuilder $qb, $from, $to)
    {
        if ($from != null) {
            $qb->andWhere('u.created >= :from')
            ->setParameter('from', $from);
        }
        if ($to != null) {
            $qb->andWhere('u.created <= :to')
            ->setParameter('to', $to);
        }
        return $qb;
    }

    /**
     * @param $site
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function createSiteQueryBuilder($site)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.locale = :locale')
            ->setParameter('locale', $site)
        ;
    }

    private function createCountryQueryBuilder($country)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.country = :country')
            ->setParameter('country', $country)
        ;
    }

    private function createCountriesQueryBuilder($countries)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->add('where', $qb->expr()->in('u.country', $countries));
        return $qb;
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param $optIn
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function addArenaOptQuery(QueryBuilder $qb, $optIn)
    {
        return $qb->andWhere('u.subscribedAlienwareEvents = :optIn')
            ->setParameter('optIn', $optIn)
        ;
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param $optIn
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function addDellOptQuery(QueryBuilder $qb, $optIn)
    {
        return $qb->andWhere('u.subscribedGamingNews = :optIn')
            ->setParameter('optIn', $optIn)
        ;
    }

    /**
     * @param \DateTime $fromDate
     * @param \DateTime $thruDate
     * @return \Doctrine\ORM\Query
     * returning the query here so that it can be iterated in ExportQueryManager while building the csv file.
     */
    public function getOptedInUserQuery($fromDate, $thruDate, $sites)
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.subscribedAlienwareEvents = 1');

        if ($fromDate) {
            $qb->andWhere('u.created >= :fromDate')->setParameter('fromDate', $fromDate);
        }

        if ($thruDate) {
            $qb->andWhere('u.created <= :thruDate')->setParameter('thruDate', $thruDate);
        }

        // users dont really belong to sites or regions, but countries. so yeah ...
        // and sites may or may not have a region. fun stuff!
        if ($sites) {
            $countries = array();
            foreach ($sites as $site) {
                $region = $site->getRegion();
                if ($region) {
                    foreach ($region->getCountries() as $country) {
                        $countries[] = $country->getCode();
                    }
                }
            }

            if(count($countries) > 0) {
                $qb->andWhere($qb->expr()->in('u.country', $countries));
            }
        }

        return $qb->getQuery();
    }
}
