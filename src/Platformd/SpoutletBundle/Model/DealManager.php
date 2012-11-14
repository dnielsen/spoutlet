<?php

namespace Platformd\SpoutletBundle\Model;

use Platformd\SpoutletBundle\Entity\GamePage;
use Platformd\SpoutletBundle\Entity\Deal;
use Doctrine\ORM\EntityManager;
use Platformd\SpoutletBundle\Entity\GamePageLocale;
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

    /**
     * Call this to save a Deal
     *
     * This must be used instead of persisting it directly
     *
     * @param Deal $deal
     * @param bool $flush
     */
    public function saveDeal(Deal $deal, $flush = true)
    {
        $this->em->persist($deal);

        $this->handleMediaFields($deal);
        $this->handleMediaGallery($deal);

        if ($flush) {
            $this->em->flush();
        }
    }

    /**
     * Finds all the Deal objects that are related to the given site.
     *
     * Orders them by newest first
     *
     * @param string $site The site/locale
     * @return \Platformd\SpoutletBundle\Entity\Deal[]
     */
    public function findAllForSiteNewestFirst($site)
    {
        return $this->getRepository()->findAllForSiteNewestFirst($site);
    }

    public function findAllOrderedByNewest()
    {
        return $this->getRepository()->findAllOrderedByNewest();
    }

    /**
     * @param string $slug
     * @return \Platformd\SpoutletBundle\Entity\Deal
     */
    public function findOneBySlug($slug, $site)
    {
        return $this->getRepository()->findOneBySlugForSite($slug, $site);
    }

    /**
     * @return \Platformd\SpoutletBundle\Entity\Deal[]
     */
    public function findFeaturedDeals($site)
    {
        return $this->getRepository()->findFeaturedDealsForSite($site);
    }

    /**
     * @param array $featuredDeals
     * @return \Platformd\SpoutletBundle\Entity\Deal[]
     */
    public function findActiveNonFeaturedDeals(array $featuredDeals, $site)
    {
        return $this->getRepository()->findAllActiveNonFeatureDealsForSite($site, $featuredDeals);
    }

    /**
     * @param array $featuredDeals
     * @return \Platformd\SpoutletBundle\Entity\Deal[]
     */
    public function findActiveDeals($site)
    {
        return $this->getRepository()->findAllActiveDealsForSite($site);
    }

    /**
     * @return \Platformd\SpoutletBundle\Entity\Deal[]
     */
    public function findExpiredDeals($site)
    {
        return $this->getRepository()->findExpiredDealsForSite($site);
    }

    /**
     * Properly persists or unsets the media fields
     *
     * @param \Platformd\SpoutletBundle\Entity\Deal $deal
     */
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

    /**
     * Handles the gallery medias persistence
     *
     * @param \Platformd\SpoutletBundle\Entity\Deal $deal
     */
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

    /**
     * @return \Platformd\SpoutletBundle\Entity\DealRepository
     */
    private function getRepository()
    {
        return $this->em->getRepository('SpoutletBundle:Deal');
    }
}
