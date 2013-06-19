<?php

namespace Platformd\VideoBundle\Model;

use Doctrine\ORM\EntityManager;
use Platformd\VideoBundle\Entity\YoutubeVideo;
use Platformd\CEVOBundle\Api\ApiManager,
    Platformd\CEVOBundle\Api\ApiException;

use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface as aclProvider,
    Symfony\Component\Security\Acl\Domain\ObjectIdentity,
    Symfony\Component\Security\Acl\Domain\UserSecurityIdentity,
    Symfony\Component\Security\Acl\Permission\MaskBuilder
;

class YoutubeManager
{
    private $em;
    private $aclProvider;
    private $apiManager;

    const YOUTUBE_ID_REGEX      = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i';
    const API_ARP_ADD_VIDEO     = 'videosubmit';
    const API_ARP_NUKE_VIDEO    = 'videonuke';
    const API_ARP_VOTE_VIDEO    = 'videovote';

    public function __construct(EntityManager $entityManager, AclProvider $aclProvider, ApiManager $apiManager)
    {
        $this->em           = $entityManager;
        $this->aclProvider  = $aclProvider;
        $this->apiManager   = $apiManager;
    }

    public function updateVideo(YoutubeVideo $video, $flush = true)
    {
        $this->em->persist($video);

        if($flush) {
            $this->em->flush();
        }
    }

    public function createVideo(YoutubeVideo $video, $flush = true, $giveArp = true)
    {
        $this->em->persist($video);

        if($flush) {
            $this->em->flush();
        }

        // ACLs
        $objectIdentity = ObjectIdentity::fromDomainObject($video);
        $acl = $this->aclProvider->createAcl($objectIdentity);
        $securityIdentity = UserSecurityIdentity::fromAccount($video->getAuthor());

        $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);
        $this->aclProvider->updateAcl($acl);

        if ($giveArp) {
            $this->arp($video->getAuthor(), self::API_ARP_ADD_VIDEO);
        }
    }

    public function deleteVideo(YoutubeVideo $video, $deletedReason, $deletedByAdmin = false)
    {
        $video->setDeleted(true);
        $video->setDeletedReason($deletedReason);

        $this->em->persist($video);
        $this->em->flush();

        $this->arp($video->getAuthor(), self::API_ARP_NUKE_VIDEO);
    }

    public function canVoteOnVideo($video, $user)
    {
        return $this->getVoteRepository()->canVoteOnVideo($video, $user);
    }

    public function addVote($vote)
    {
        $this->em->persist($vote);
        $this->em->flush();

        //$this->arp($vote->getUser(), self::API_ARP_VOTE_VIDEO);
    }

    public function getVideoInfo($youtubeId)
    {
        if(!$youtubeId) {
            return false;
        }

        $url = sprintf('http://gdata.youtube.com/feeds/api/videos/%s?alt=jsonc&v=2', $youtubeId);

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:'));

        $result = json_decode(curl_exec($curl), true);

        return $result;
    }

    public function getYoutubeRestrictions($youtubeId)
    {
        $restrictions = array();

        $result = $this->getVideoInfo($youtubeId);

        if(isset($result['data']['restrictions'])) {
            $restrictions = $result['data']['restrictions'][0];
        }

        return $restrictions;
    }

    public function getRestrictionsAndErrors($youtubeId)
    {
        $restrictions   = array();
        $errors         = array();

        $result = $this->getVideoInfo($youtubeId);

        if(isset($result['data']['restrictions'])) {
            $restrictions = $result['data']['restrictions'][0];
        }

        if(isset($result['error'])) {
            $errors = $result['error'];
        }

        return array(
            'restrictions'  => $restrictions,
            'errors'        => $errors
        );
    }

    public function findBy(array $args = array())
    {
        return $this->getRepository()->findBy($args);
    }

    public function findOneBy(array $args = array())
    {
        return $this->getRepository()->findOneBy($args);
    }

    public function findVideoBySlug($slug, $deleted = false)
    {
        return $this->getRepository()->findOneBy(array('slug' => $slug, 'deleted' => $deleted));
    }

    public function findVideoById($id, $deleted = false)
    {
        return $this->getRepository()->findOneBy(array('id' => $id, 'deleted' => $deleted));
    }

    public function findVideosByUser($user, $limit = 9, $id = 0)
    {
        return $this->getRepository()->findVideosByUser($user, $limit, $id);
    }

    public function findCurrentlyWatchedVideos($site, $limit = 9, $numberOfDays = 7)
    {
        return $this->getRepository()->findCurrentlyWatchedVideos($site, $limit, $numberOfDays);
    }

    public function findCurrentlyWatchedVideosForCountry($site, $country, $limit = 9, $numberOfDays = 7)
    {
        return $this->getRepository()->findCurrentlyWatchedVideosForCountry($site, $country, $limit, $numberOfDays);
    }

    public function findFeaturedVideos($site, $limit = 9)
    {
        return $this->getRepository()->findFeaturedVideos($site, $limit);
    }

    public function findFeaturedVideosForCountry($site, $country, $limit = 9)
    {
        return $this->getRepository()->findFeaturedVideosForCountry($site, $country, $limit);
    }

    public function findPopularVideos($site, $limit = 9)
    {
        return $this->getRepository()->findPopularVideos($site, $limit);
    }

    public function findPopularVideosForCountry($site, $country, $limit = 9)
    {
        return $this->getRepository()->findPopularVideosForCountry($site, $country, $limit);
    }

    public function findPopularVideosByCategory($category)
    {
        return $this->getRepository()->findPopularVideosByCategory($category);
    }

    public function findPopularVideosByCategoryForCountry($category, $country)
    {
        return $this->getRepository()->findPopularVideosByCategoryForCountry($category, $country);
    }

    public function findCurrentlyWatchedVideosByCategory($category)
    {
        return $this->getRepository()->findCurrentlyWatchedVideosByCategory($category);
    }

    public function findCurrentlyWatchedVideosByCategoryForCountry($category, $country)
    {
        return $this->getRepository()->findCurrentlyWatchedVideosByCategoryForCountry($category, $country);
    }

    public function findVideosByCategory($category, $limit = 3)
    {
        return $this->getRepository()->findVideosByCategory($category, $limit);
    }

    public function findVideosByCategoryForCountry($category, $country, $limit = 3)
    {
        return $this->getRepository()->findVideosByCategoryForCountry($category, $country, $limit);
    }

    public function findAllCategories()
    {
        return $this->getGalleryRepository()->findAll();
    }

    public function findAllCategoriesForSite($site)
    {
        return $this->getGalleryRepository()->findAllGalleriesByCategoryForSite($site, 'video');
    }

    public function findCategoryByName($name)
    {
        return $this->getGalleryRepository()->findOneBy(array('name' => $name));
    }

    public function findCategoryBySlug($slug)
    {
        return $this->getGalleryRepository()->findOneBy(array('slug' => $slug));
    }

    public function findVideosForTabAndCountry($tab, $site, $country, $limit = 9)
    {
        switch ($tab) {
            case 'popular':
                return $this->findPopularVideosForCountry($site, $country, $limit);
                break;

            case 'currently_watched':
                return $this->findCurrentlyWatchedVideosForCountry($site, $country, $limit);
                break;

            case 'categories':
                return $this->getAllVideosForCategoryTabForCountry($site, $country);
                break;

            default:
                return null;
                break;
        }
    }

    public function findVideoMetrics($fromDate, $thruDate, $keyWords)
    {
        return $this->getRepository()->findVideoMetrics($fromDate, $thruDate, $keyWords);
    }

    public function findUserAccountVideos($user, $maxPerPage = 10, $currentPage = 1)
    {
        return $this->getRepository()->findUserAccountVideos($user, $maxPerPage, $currentPage);
    }

    public function findVideosForGroup($group)
    {
        return $this->getRepository()->findVideosForGroup($group);
    }

    private function getAllVideosForCategoryTab($site)
    {
        $categories = $this->findAllCategoriesForSite($site);
        $allVideos  = array();

        foreach ($categories as $category) {
            $videos = $this->findVideosByCategory($category);
            array_push($allVideos, array('category' => $category, 'videos' => $videos));
        }

        return $allVideos;
    }

    private function getAllVideosForCategoryTabForCountry($site, $country)
    {
        $categories = $this->findAllCategoriesForSite($site);
        $allVideos  = array();

        foreach ($categories as $category) {
            $videos = $this->findVideosByCategoryForCountry($category, $country);
            array_push($allVideos, array('category' => $category, 'videos' => $videos));
        }

        return $allVideos;
    }

    private function arp($user, $tag)
    {
        try {
            $response = $this->apiManager->GiveUserXp($tag, $user->getCevoUserId());
        } catch (ApiException $e) {

        }
    }

    private function getRepository()
    {
        return $this->em->getRepository('VideoBundle:YoutubeVideo');
    }

    private function getVoteRepository()
    {
        return $this->em->getRepository('VideoBundle:YoutubeVote');
    }

    private function getGalleryRepository()
    {
        return $this->em->getRepository('SpoutletBundle:Gallery');
    }
}
