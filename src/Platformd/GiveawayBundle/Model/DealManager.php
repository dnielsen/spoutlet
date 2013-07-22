<?php

namespace Platformd\GiveawayBundle\Model;

use Platformd\GiveawayBundle\Entity\Deal;
use Doctrine\ORM\EntityManager;
use Knp\MediaBundle\Util\MediaUtil;
use Platformd\SpoutletBundle\Util\CacheUtil;
use Platformd\SpoutletBundle\Util\SiteUtil;
use Platformd\GiveawayBundle\Entity\Repository\DealCodeRepository;
use Platformd\GiveawayBundle\Entity\Repository\DealRepository;
use Platformd\GiveawayBundle\ViewModel\deal_show_data;
use Platformd\GiveawayBundle\ViewModel\deal_index_data;
use Platformd\SpoutletBundle\Entity\ThreadRepository;
use Platformd\SpoutletBundle\Entity\Thread;
use Platformd\SpoutletBundle\Link\LinkableManager;
use Platformd\MediaBundle\Exposer\MediaPathResolver;
use Platformd\SpoutletBundle\Model\CommentManager;

class DealManager
{
    private $em;
    private $mediaUtil;
    private $cacheUtil;
    private $dealRepo;
    private $siteUtil;
    private $keyCounterUtil;
    private $dealCodeRepo;
    private $threadRepo;
    private $linkableManager;
    private $mediaPathResolver;
    private $commentManager;
    private $varnishUtil;
    private $router;

    public function __construct(EntityManager $em, MediaUtil $mediaUtil, CacheUtil $cacheUtil, DealRepository $dealRepo, SiteUtil $siteUtil, DealCodeRepository $dealCodeRepo, ThreadRepository $threadRepo, LinkableManager $linkableManager, MediaPathResolver $mediaPathResolver, CommentManager $commentManager, $varnishUtil, $router)
    {
        $this->em                   = $em;
        $this->mediaUtil            = $mediaUtil;
        $this->cacheUtil            = $cacheUtil;
        $this->dealRepo             = $dealRepo;
        $this->siteUtil             = $siteUtil;
        $this->dealCodeRepo         = $dealCodeRepo;
        $this->threadRepo           = $threadRepo;
        $this->linkableManager      = $linkableManager;
        $this->mediaPathResolver    = $mediaPathResolver;
        $this->commentManager       = $commentManager;
        $this->varnishUtil          = $varnishUtil;
        $this->router               = $router;
    }

    public function getAnonDealIndexData() {
        $featuredDealsArr = array();
        $allDealsArr      = array();
        $expiredDealsArr  = array();
        $commentsArr      = array();
        $siteId           = $this->siteUtil->getCurrentSiteCached()->getId();
        $featuredDeals    = $this->findFeaturedDeals($siteId);
        $mainDeal         = empty($featuredDeals) ? null : $featuredDeals[0];
        $allDeals         = $this->findActiveDeals($siteId);
        $expiredDeals     = $this->findExpiredDeals($siteId);
        $comments         = $this->commentManager->findMostRecentCommentsByThreadPrefixWithObjects(Deal::COMMENT_PREFIX, 5);

        $nextExpiryIn     = null;

        foreach ($featuredDeals as $deal) {
            $featuredDealsArr[] = array(
                'url'         => $this->linkableManager->link($deal),
                'thumbnail'   => $deal->getThumbnailLarge() ? $this->mediaPathResolver->getPath($deal->getThumbnailLarge(), array()) : null,
                'name'        => $deal->getName(),
                'ends_at_utc' => $deal->getEndsAtUtc(),
            );

            $endsIn = $deal->getEndsAtUtc() ? $deal->getEndsAtUtc()->format('U') - strtotime('now') : 0;

            if ($endsIn && (!$nextExpiryIn || $endsIn < $nextExpiryIn)) {
                $nextExpiryIn = $endsIn;
            }
        }

        foreach ($allDeals as $deal) {
            $allDealsArr[] = array(
                'url'         => $this->linkableManager->link($deal),
                'thumbnail'   => $deal->getThumbnailLarge() ? $this->mediaPathResolver->getPath($deal->getThumbnailLarge(), array()) : null,
                'name'        => $deal->getName(),
                'ends_at_utc' => $deal->getEndsAtUtc(),
            );

            $endsIn = $deal->getEndsAtUtc() ? $deal->getEndsAtUtc()->format('U') - strtotime('now') : 0;

            if ($endsIn && (!$nextExpiryIn || $endsIn < $nextExpiryIn)) {
                $nextExpiryIn = $endsIn;
            }
        }

        foreach ($expiredDeals as $deal) {
            $expiredDealsArr[] = array(
                'url'         => $this->linkableManager->link($deal),
                'thumbnail'   => $deal->getThumbnailLarge() ? $this->mediaPathResolver->getPath($deal->getThumbnailLarge(), array()) : null,
                'name'        => $deal->getName(),
            );
        }

        foreach ($comments as $commentArray) {
            $comment    = $commentArray['comment'];
            $deal       = $commentArray['object'];

            $commentsArr[] = array(
                'deal_thumbnail'    => $deal->getThumbnailLarge() ? $this->mediaPathResolver->getPath($deal->getThumbnailLarge(), array()) : null,
                'deal_name'         => $deal->getName(),
                'deal_url'          => $this->linkableManager->link($deal),
                'author'            => $comment->getAuthor()->getUsername(),
                'created_at'        => $comment->getCreatedAt(),
            );
        }

        $mainDealArr = array(
            'banner'        => $mainDeal ? $mainDeal->getBanner() ? $this->mediaPathResolver->getPath($mainDeal->getBanner(), array()) : null : null,
            'url'           => $mainDeal ? $this->linkableManager->link($mainDeal) : null,
            'ends_at_utc'   => $mainDeal ? $mainDeal->getEndsAtUtc() : null,
            'has_expired'   => $mainDeal ? $mainDeal->hasExpired() : null,
        );

        $data                     = new deal_index_data();
        $data->featured_deals     = $featuredDealsArr;
        $data->main_deal          = empty($mainDealArr)     ? null : $mainDealArr;
        $data->all_deals          = empty($allDealsArr)     ? null : $allDealsArr;
        $data->expired_deals      = empty($expiredDealsArr) ? null : $expiredDealsArr;
        $data->comments           = $commentsArr;
        $data->next_expiry_in     = $nextExpiryIn;

        return $data;
    }

    public function saveDeal(Deal $deal, $flush = true)
    {
        $this->em->persist($deal);

        $this->handleMediaFields($deal);
        $this->handleMediaGallery($deal);

        if ($flush) {
            $this->em->flush();
        }

        $indexPath = $this->router->generate('deal_list');
        $dealPath  = $this->router->generate('deal_show', array('slug' => $deal->getSlug()));

        try {
            $this->varnishUtil->banCachedObject($indexPath);
            $this->varnishUtil->banCachedObject($dealPath);
        } catch (Exception $e) {
            throw new \Exception('Could not ban.');
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

    public function findFeaturedDeals($siteId)
    {
        return $this->getRepository()->findFeaturedDealsForSiteId($siteId);
    }

    public function findActiveNonFeaturedDeals(array $featuredDeals, $site)
    {
        return $this->getRepository()->findAllActiveNonFeatureDealsForSite($site, $featuredDeals);
    }

    public function findActiveDeals($siteId)
    {
        return $this->getRepository()->findAllActiveDealsForSiteId($siteId);
    }

    public function findExpiredDeals($siteId)
    {
        return $this->getRepository()->findExpiredDealsForSiteId($siteId);
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
        foreach ($deal->getMediaGalleryMedias() as $media) {
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
