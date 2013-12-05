<?php

namespace Platformd\SearchBundle\Model;

use Doctrine\ORM\EntityManager;
use Platformd\SearchBundle\Entity\SearchTerm;
use Platformd\EventBundle\Entity\Event;
use Platformd\TagBundle\Model\TaggableInterface;
use Platformd\SearchBundle\QueueMessage\SearchIndexQueueMessage;

use Symfony\Component\HttpFoundation\Response;
use Platformd\SpoutletBundle\HPCloud\HPCloudPHP;

class SearchManager
{
    private $domainName;
    private $domainId;
    private $allowIndex;
    private $devMode;
    private $devUser;
    private $searchPrefix;
    private $em;
    private $tagManager;
    private $translator;
    private $s3;
    private $privateBucket;
    private $queueUtil;

    private $calendarMethod = "2011-02-01";

    private static $entityTypeMap = array(
        'global_event' => 'EventBundle:GlobalEvent',
        'group_event'  => 'EventBundle:GroupEvent',
        'group'        => 'GroupBundle:Group',
        'giveaway'     => 'GiveawayBundle:Giveaway',
        'deal'         => 'GiveawayBundle:Deal',
        'news'         => 'NewsBundle:News',
        'video'        => 'VideoBundle:YoutubeVideo',
    );

    const SEARCH_RESULTS_PER_PAGE = 10;

    public function __construct($domainName, $domainId, $allowIndex, $devMode, $devUser, EntityManager $em, $tagManager, $translator, $s3, $privateBucket, $queueUtil, $searchPrefix, $hpcloud_accesskey='', $hpcloud_secreatkey='', $hpcloud_tenantid='', $hpcloud_url='', $hpcloud_container='',$objectStorage='') {
        $this->domainName        = $domainName;
        $this->domainId          = $domainId;
        $this->allowIndex        = $allowIndex;
        $this->devMode           = $devMode;
        $this->devUser           = $devUser;
        $this->em                = $em;
        $this->tagManager        = $tagManager;
        $this->translator        = $translator;
        $this->s3                = $s3;
        $this->privateBucket     = $privateBucket;
        $this->queueUtil         = $queueUtil;
        $this->searchPrefix      = $searchPrefix;
      
        $this->objectStorage     = $objectStorage;
        $this->hpcloud_container = $hpcloud_container;
        $this->hpcloud_url       = $hpcloud_url;
	      $this->hpCloudObj        = new HPCloudPHP($hpcloud_accesskey,$hpcloud_secreatkey,$hpcloud_tenantid);
        
    }

    public function search($criteria, $params = array(), $site, $category = null)
    {
        $response = $this->performSearch($criteria, $params, $site, $category);

        if (null !== $category) {
            $facetResponse = $this->performSearch($criteria, $params, $site, null);
        }

        if (!$this->domainName || !$this->domainId) {
            return array();
        }

        $hits                = $response['hits'];
        $resultCount         = $hits['found'];

        $data                = array();
        $data['resultCount'] = $resultCount;

        if ($resultCount > 0) {
            $resultsData    = $hits['hit'];
            $results        = array();

            foreach ($resultsData as $resultData) {
                $resultData          = $resultData['data'];

                $repoClass           = isset(self::$entityTypeMap[$resultData['entity_type'][0]]) ? self::$entityTypeMap[$resultData['entity_type'][0]] : $resultData['entity_type'][0];
                $repo                = $this->em->getRepository($repoClass);

                if ($repo) {
                    $result['id']    = $resultData['entity_id'][0];
                    $entity = $repo->find($result['id']);

                    if ($entity) {
                        $result['relevance'] = $resultData['text_relevance'][0];
                        $result['entity']    = $entity;
                        $result['category']  = $resultData['type'][0];
                        $results[]           = $result;
                    }
                }
            }

            $data['results'] = $results;
        }

        $facetResponse = $category === null ? $response : $facetResponse;

        if ($facetResponse['hits']['found'] > 0) {
            $facetData = $facetResponse['facets']['f_type']['constraints'];
            $facets    = array();

            foreach ($facetData as $facet) {
                $facets[$facet['value']] = $facet['count'];
            }

            $data['facets']         = $facets;
            $data['allResultCount'] = $facetResponse['hits']['found'];
        }

        return $data;
    }

    private function performSearch($criteria, $params = array(), $site, $category = null)
    {
        $searchEndpoint     = 'http://search-'.$this->domainName.'-'.$this->domainId.'.us-east-1.cloudsearch.amazonaws.com/'.$this->calendarMethod;

        $defaultParams      = array(
            'return-fields' => 'type,entity_id,entity_type,-text_relevance',
            'facet'         => 'f_type',
            'size'          => self::SEARCH_RESULTS_PER_PAGE,
        );

        $params        = array_merge($defaultParams, $params);

        $criteria      = urlencode(($site->getDefaultLocale() == 'ja' ? "'".$this->encodeString($criteria)."'" : "'".$criteria."'"));
        $siteQuery     = $site ? "%20site:'".$site->getFullDomain()."'" : "";
        $categoryQuery = $category ? "%20f_type:'".$category."'" : '';
        $devQuery      = ($this->devMode && $this->devUser) ? "%20dev_mode:1%20dev_user:'".$this->devUser."'" : '';

        $query         = $criteria . $siteQuery . $categoryQuery . $devQuery;

        return $this->call($searchEndpoint ."/search?bq=(and%20".$query.")&".http_build_query($params), "GET", array());
    }

    public function getAllDocuments()
    {
        $searchEndpoint = 'http://search-'.$this->domainName.'-'.$this->domainId.'.us-east-1.cloudsearch.amazonaws.com/'.$this->calendarMethod;
        $devQuery       = ($this->devMode && $this->devUser) ? "%20dev_user:'".$this->devUser."'" : '';
        $devModeInt     = (int) $this->devMode;
        $result         = $this->call($searchEndpoint ."/search?bq=(and".$devQuery."%20dev_mode:".$devModeInt.")", "GET", array());

        if (isset($result['hits'])) {
            $size = $result['hits']['found'];

            if ($size > 0) {
                return $this->call($searchEndpoint ."/search?bq=(and".$devQuery."%20dev_mode:".$devModeInt.")&size=".$size, "GET", array());
            }
        }

        return $result;
    }

    public function document($data)
    {
        // Check if $data is already json_encoded
        if (json_decode($data) === null) {
            $data = json_encode($data);
        }

        $documentEndpoint = 'http://doc-'.$this->domainName.'-'.$this->domainId.'.us-east-1.cloudsearch.amazonaws.com/'.$this->calendarMethod;

        return $this->call($documentEndpoint ."/documents/batch", "POST", $data);
    }

    private function call($url, $method, $parameters)
    {
        $curl2 = curl_init();

        if ($method == "POST")
        {
            curl_setopt($curl2, CURLOPT_POST, true);
            curl_setopt($curl2, CURLOPT_POSTFIELDS, $parameters);

            curl_setopt($curl2, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($parameters))
            );

        }

        curl_setopt($curl2, CURLOPT_URL, $url);
        curl_setopt($curl2, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl2);

        return json_decode($result, true);
    }

    public function getExtraData($entity)
    {
        $class = join('', array_slice(explode('\\', get_class($entity)), -1));

        switch ($class) {
            case 'GroupEvent':
            case 'GlobalEvent':
                $data = array();

                if ($entity->getRegistrationOption() == Event::REGISTRATION_ENABLED) {
                    $data['event_attendee_count'] = $entity->getAttendeeCount();
                }

                return $data;
                break;

            case 'Group':
                $memberCount = $this->em->getRepository('GroupBundle:Group')->getMembershipCountByGroup($entity);
                return array('group_member_count' => $memberCount);

            case 'YoutubeVideo':
                return array('video_views' => $entity->getViews(), 'video_votes' => $entity->getVotes()->count());

            default:
                return null;
                break;
        }
    }

    public function logSearch($criteria, $flush = true)
    {
        $term = new SearchTerm($criteria);
        $this->em->persist($term);

        if ($flush) {
            $this->em->flush();
        }

        return true;
    }

    public function isIndexingEnabled()
    {
        return $this->allowIndex;
    }

    public function getEntityIndexData($entity)
    {
        $indexData = array();
        $enabledSites = array();

        foreach ($entity->getSites() as $site) {

            $indexData[]    = $this->getIndexData($entity, $site, true);
            $enabledSites[] = $site->getId();
        }

        $notEnabledSites = $this->em->getRepository('SpoutletBundle:Site')->findAllWithIdNotIn($enabledSites);

        foreach ($notEnabledSites as $delSite) {

            $indexData[] = $this->getIndexData($entity, $delSite, false);
        }

        return $indexData;
    }

    public function getIndexData($entity, $site, $add = true)
    {
        $id = ($this->searchPrefix ? $this->searchPrefix.'_' : '') . ($this->devMode && $this->devUser ? $this->devUser.'_' : '') . $entity->getSearchId().'_'.$site->getId();

        $indexData = array(
            'type'    => $add ? 'add' : 'delete',
            'id'      => $id,
            'version' => time(),
        );

        if ($add) {
            $indexData['lang'] = 'en';
            $indexData['fields'] = array(
                'type'        => $entity->getSearchFacetType(),
                'title'       => $site->getDefaultLocale() == 'ja' ? $this->encodeString($entity->getSearchTitle()) : $entity->getSearchTitle(),
                'blurb'       => $site->getDefaultLocale() == 'ja' ? $this->encodeString(strip_tags($entity->getSearchBlurb())) : strip_tags($entity->getSearchBlurb()),
                'site'        => $site->getFullDomain(),
                'entity_id'   => $entity->getId(),
                'entity_type' => $entity->getSearchEntityType(),
            );

            $tags = null;

            if ($entity instanceof TaggableInterface) {
                $this->tagManager->loadTagging($entity);
                $tags = $this->tagManager->getTagNames($entity);
            }

            if ($tags && count($tags) > 0) {
                $indexData['fields']['tags'] = $tags;
            }

            if ($this->devMode && $this->devUser) {
                $indexData['fields']['dev_mode'] = 1;
                $indexData['fields']['dev_user'] = $this->devUser;
            }
        }

        return $indexData;
    }

    public function indexEntity($entity, $remove = false)
    {
        $bucket        = $this->privateBucket;

        if ($this->isIndexingEnabled()) {
            if ($remove || $entity->getDeleteSearchDocument()) {

                $indexData = array();

                foreach ($entity->getSites() as $site) {

                    $indexData[] = $this->getIndexData($entity, $site, false);
                }

            } else {
                $indexData = $this->getEntityIndexData($entity);
            }

            $jsonData = json_encode($indexData);
            $filename = SearchIndexQueueMessage::SEARCH_INDEX_S3_PREFIX.'/'.md5($jsonData.time()).'.json';
            
           if($this->objectStorage == 'HpObjectStorage') {
             $response = $this->hpCloudObj->create_object($bucket, $filename,array(
                'body'          => $jsonData,
                'encryption'    => 'AES256',
                'contentType'   => 'text/json',            
             ));
           } else {
           
              $response = $this->s3->create_object($bucket, $filename, array(
                'body'          => $jsonData,
                'acl'           => \AmazonS3::ACL_PRIVATE,
                'encryption'    => 'AES256',
                'contentType'   => 'text/json',
              ));
            
            $response_data = $response->isOk();
           }
           
            if ($response_data) {

                $message = new SearchIndexQueueMessage();
                $message->bucket = $bucket;
                $message->filename = $filename;

                $result = $this->queueUtil->addToQueue($message);

                if (!$result) {
                    die('Could not add you to the queue... please try again shortly.');
                }

            } else {
                die('Could not upload index data to s3... please try again shortly.');
            }
        }
    }

    private function encodeString($string, $urlEncode = false)
    {
        $characters = $this->mb_str_split($string);

        $add = (count($characters) > 1 ? ' ' : '');

        for ($i=0; $i < count($characters); $i++) {

            if ($this->isCJK($characters[$i])) {
                $start          = ($i > 0 ? ' ' : '');
                $end            = ($i <= count($characters) ? ' ' : '');
                $characters[$i] = $start.'CJK'.bin2hex($characters[$i]).$end;
            }
        }

        return ($urlEncode ? urlencode(implode('', $characters)) : implode('', $characters));
    }

    public function getSearchableEntities()
    {
        return self::$entityTypeMap;
    }

    private function getCJKUnicodeRanges() {
        return array(
            "[\x{2E80}-\x{2EFF}]",      # CJK Radicals Supplement
            "[\x{2F00}-\x{2FDF}]",      # Kangxi Radicals
            "[\x{2FF0}-\x{2FFF}]",      # Ideographic Description Characters
            "[\x{3000}-\x{303F}]",      # CJK Symbols and Punctuation
            "[\x{3040}-\x{309F}]",      # Hiragana
            "[\x{30A0}-\x{30FF}]",      # Katakana
            "[\x{3100}-\x{312F}]",      # Bopomofo
            "[\x{3130}-\x{318F}]",      # Hangul Compatibility Jamo
            "[\x{3190}-\x{319F}]",      # Kanbun
            "[\x{31A0}-\x{31BF}]",      # Bopomofo Extended
            "[\x{31F0}-\x{31FF}]",      # Katakana Phonetic Extensions
            "[\x{3200}-\x{32FF}]",      # Enclosed CJK Letters and Months
            "[\x{3300}-\x{33FF}]",      # CJK Compatibility
            "[\x{3400}-\x{4DBF}]",      # CJK Unified Ideographs Extension A
            "[\x{4DC0}-\x{4DFF}]",      # Yijing Hexagram Symbols
            "[\x{4E00}-\x{9FFF}]",      # CJK Unified Ideographs
            "[\x{A000}-\x{A48F}]",      # Yi Syllables
            "[\x{A490}-\x{A4CF}]",      # Yi Radicals
            "[\x{AC00}-\x{D7AF}]",      # Hangul Syllables
            "[\x{F900}-\x{FAFF}]",      # CJK Compatibility Ideographs
            "[\x{FE30}-\x{FE4F}]",      # CJK Compatibility Forms
            "[\x{1D300}-\x{1D35F}]",    # Tai Xuan Jing Symbols
            "[\x{20000}-\x{2A6DF}]",    # CJK Unified Ideographs Extension B
            "[\x{2F800}-\x{2FA1F}]"     # CJK Compatibility Ideographs Supplement
        );
    }

    private function isCJK($string) {
        $regex = '/' . implode('|', $this->getCJKUnicodeRanges()) . '/u';
        return preg_match($regex, $string);
    }

    private function mb_str_split( $string ) {
        return preg_split('/(?<!^)(?!$)/u', $string );
    }
}
