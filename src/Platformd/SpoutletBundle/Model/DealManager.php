<?php

namespace Platformd\SpoutletBundle\Model;

use Platformd\SpoutletBundle\Entity\GamePage;
use Platformd\SpoutletBundle\Entity\Deal;
use Doctrine\ORM\EntityManager;
use Platformd\SpoutletBundle\Entity\GamePageLocale;
use Symfony\Component\HttpFoundation\Session;
use Knp\MediaBundle\Util\MediaUtil;
use Platformd\SpoutletBundle\Locale\LocalesRelationshipHelper;

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

    private $session;

    private $mediaUtil;

    private $localesHelper;

    public function __construct(EntityManager $em, Session $session, MediaUtil $mediaUtil, LocalesRelationshipHelper $localesHelper)
    {
        $this->em = $em;
        $this->session = $session;
        $this->mediaUtil = $mediaUtil;
        $this->localesHelper = $localesHelper;
    }

    /**
     * Call this to save a Deal
     *
     * This must be used instead of persisting it directly
     *
     * @param \Platformd\SpoutletBundle\Entity\Deal $deal
     */
    public function saveDeal(Deal $deal)
    {
        $this->em->persist($deal);

        $this->localesHelper->processLocalesSave($deal);
        $this->handleMediaFields($deal);
        $this->handleMediaGallery($deal);

        $this->em->flush();
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

    /**
     * @param string $slug
     * @return \Platformd\SpoutletBundle\Entity\Deal
     */
    public function findOneBySlug($slug)
    {
        return $this->getRepository()->findOneBySlugForSite($slug, $this->getDatabaseSiteKey());
    }

    /**
     * @return \Platformd\SpoutletBundle\Entity\Deal[]
     */
    public function findFeaturedDeals()
    {
        return $this->getRepository()->findFeaturedDealsForSite($this->getDatabaseSiteKey());
    }

    /**
     * @param array $featuredDeals
     * @return \Platformd\SpoutletBundle\Entity\Deal[]
     */
    public function findActiveNonFeaturedDeals(array $featuredDeals)
    {
        return $this->getRepository()->findAllActiveNonFeatureDealsForSite($this->getDatabaseSiteKey(), $featuredDeals);
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

        if (!$mUtil->persistRelatedMedia($deal->getThumbnailComment())) {
            $deal->setThumbnailComment(null);
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

    private function getDatabaseSiteKey()
    {
        return $this->session->getLocale();
    }
}
