<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * HomepageBannerRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class HomepageBannerRepository extends EntityRepository
{
    public function findAllWithoutLocaleOrderedByNewest()
    {
        return $this->createQueryBuilder('h')
            ->orderBy('h.created', 'DESC')
            ->getQuery()
            ->execute()
            ;
    }

    public function findForLocale($locale)
    {

        return $this
            ->createQueryBuilder('h')
            ->where('h.locale = ?0')
            ->orderBy('h.position', 'ASC')
            ->getQuery()
            ->execute(array($locale));
    }

    public function findForSite($site, $limit=null)
    {
        $qb = $this
            ->createQueryBuilder('h')
            ->leftJoin('h.sites', 's')
            ->where('s.id = :siteId')
            ->setParameter('siteId', $site->getId());

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        $results = $qb->getQuery()->execute();

        $positions = array();
        $bannersList = array();
        $banners = array();

        foreach($results as $banner) {
            $sitesPositions = $banner->getSitesPositions();
            $positions[$banner->getId()] = $sitesPositions[$site->getId()];
            $bannersList[$banner->getId()] = $banner;
        }

        asort($positions);

        foreach($positions as $bannerId => $value) {
            $banners[] = $bannersList[$bannerId];
        }

        return $banners;
    }
}
