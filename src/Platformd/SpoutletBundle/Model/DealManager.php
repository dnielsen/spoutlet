<?php

namespace Platformd\SpoutletBundle\Model;

use Platformd\SpoutletBundle\Entity\GamePage;
use Platformd\GiveawayBundle\Entity\Deal;
use Doctrine\ORM\EntityManager;
use Knp\MediaBundle\Util\MediaUtil;

/**
 * Manager for GamePage:
 *
 *  * Handles our special logic for locales
 *  * Saves the media fields
 *  * saves the media gallery
 */
class DealManager
{
    private $em;
    private $mediaUtil;

    public function __construct(EntityManager $em, MediaUtil $mediaUtil)
    {
        $this->em = $em;
        $this->mediaUtil = $mediaUtil;
    }

    public function saveDeal(Deal $deal, $flush = true)
    {
        $this->em->persist($deal);

        $this->handleMediaFields($deal);
        $this->handleMediaGallery($deal);

        if ($flush) {
            $this->em->flush();
        }
    }

    public function findAllForSiteNewestFirst($site)
    {
        return $this->getRepository()->findAllForSiteNewestFirst($site);
    }

    public function findAllOrderedByNewest()
    {
        return $this->getRepository()->findAllOrderedByNewest();
    }

    public function findOneBySlug($slug, $site)
    {
        return $this->getRepository()->findOneBySlugForSite($slug, $site);
    }

    public function findFeaturedDeals($site)
    {
        return $this->getRepository()->findFeaturedDealsForSite($site);
    }

    public function findActiveNonFeaturedDeals(array $featuredDeals, $site)
    {
        return $this->getRepository()->findAllActiveNonFeatureDealsForSite($site, $featuredDeals);
    }

    public function findActiveDeals($site)
    {
        return $this->getRepository()->findAllActiveDealsForSite($site);
    }

    public function findExpiredDeals($site)
    {
        return $this->getRepository()->findExpiredDealsForSite($site);
    }

    private function handleMediaFields(Deal $deal)
    {
        $mUtil = $this->mediaUtil;

        if (!$mUtil->persistRelatedMedia($deal->getBanner())) {
            $deal->setBanner(null);
        }

        if (!$mUtil->persistRelatedMedia($deal->getThumbnailLarge())) {
            $deal->setThumbnailLarge(null);
        }

        if (!$mUtil->persistRelatedMedia($deal->getClaimCodeButton())) {
            $deal->setClaimCodeButton(null);
        }

        if (!$mUtil->persistRelatedMedia($deal->getVisitWebsiteButton())) {
            $deal->setVisitWebsiteButton(null);
        }
    }

    private function handleMediaGallery(Deal $deal)
    {
        /** @var $media \Platformd\MediaBundle\Entity\Media */
        foreach ($deal->getMediaGalleryMedias() as $media) {
            // either persist it, or remove it from the collection
            if (!$this->mediaUtil->persistRelatedMedia($media)) {
                $deal->getMediaGalleryMedias()->removeElement($media);
            }
        }
    }

    private function getRepository()
    {
        return $this->em->getRepository('GiveawayBundle:Deal');
    }
}
