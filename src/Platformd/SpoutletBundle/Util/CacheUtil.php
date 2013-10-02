<?php

namespace Platformd\SpoutletBundle\Util;

use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Platformd\SpoutletBundle\Exception\CacheFailureException;

class CacheUtil
{
    const LOG_MESSAGE_PREFIX             = '[CacheUtil] ';
    const LOCK_PREFIX_FOR_CACHE_KEYS     = 'LOCK::';
    const MAX_ATTEMPTS                   = 15;
    const SECONDS_BETWEEN_ATTEMPTS       = 2;
    const MAX_CACHE_DURATION_SECONDS     = 2592000; // 30 days in seconds - if you go above this in memcached it is assumed you are passing a unix timestamp
    const MIN_CACHE_DURATION_SECONDS     = 1;
    const MAX_LOCK_DURATION_SECONDS      = 15; // any more than this and caching isn't going to help you... bring the generation function down into smaller chunks and cache the subsets of data
    const MIN_LOCK_DURATION_SECONDS      = 1;
    const DEFAULT_CACHE_DURATION_SECONDS = 60;
    const DEFAULT_LOCK_DURATION_SECONDS  = 10;
    const MAX_CACHE_KEY_LENGTH           = 250; # this is the max key length of memcached

    private $cache;
    private $logger;
    private $allowCaching;
    private $lockStack = array();
    private $localCache = array();

    public function __construct(\Memcached $cache, $logger, $allowCaching)
    {
        $this->cache        = $cache;
        $this->logger       = $logger;
        $this->allowCaching = $allowCaching;
    }

    public function __destruct() {
        foreach ($this->lockStack as $lock) {
            try {
                $this->cache->delete($lock);
            } catch (\Exception $ex) {
                // just need to give it our best shot... no point logging as logger may already be dead and we are failing because of that
            }
        }
    }

    public function getLock($forKey, $lockDurationSeconds) {
        $lockKeyName = self::LOCK_PREFIX_FOR_CACHE_KEYS.$forKey;
        $gotLock     = $this->cache->add($lockKeyName, 1, $lockDurationSeconds);

        if (!$gotLock) {
            if ($this->cache->getResultCode() !== \Memcached::RES_NOTSTORED) {
                $this->logger->err(self::LOG_MESSAGE_PREFIX.'Cache could not add locking key and cache said "'.$this->cache->getResultCode().'" - going to retry.');
            } else {
                $this->logger->debug(self::LOG_MESSAGE_PREFIX.'Someone else has the lock - going to start again as they will likely generate the content. Going to retry.');
            }

            return false;
        }

        $this->logger->debug(self::LOG_MESSAGE_PREFIX.'Got lock "'.$lockKeyName.'".');
        $this->lockStack[] = $lockKeyName;

        return true;
    }

    private function releaseLastLock() {
        $lock = array_pop($this->lockStack);

        if ($lock === null) {
            $this->logger->warn(self::LOG_MESSAGE_PREFIX.'Was asked to releaseLock but the last lock was null OR there are no more locks to release.');
            return;
        }

        $this->logger->debug(self::LOG_MESSAGE_PREFIX.'Releasing lock - lock = "'.$lock.'".');
        $this->cache->delete($lock);
    }

    public function setCurrentSiteId(SiteUtil $siteUtil) {
        $this->currentSiteId = $siteUtil->getCurrentSiteCached()->getId();
        $this->logger->debug(self::LOG_MESSAGE_PREFIX.'CurrentSiteId set to "'.$this->currentSiteId.'".');
    }

    public function releaseCurrentLock() {

        $currentLock = $this->currentLock;

        if (!$currentLock) {
            $this->logDebug('releaseCurrentLock called but no lock to delete.');
            return;
        }

        $this->logDebug('Lock deleted.');
        $this->cache->delete($this->currentLock);

        $this->currentLock = null;
    }

    private function getFromCache($longKey) {

        $data = $this->cache->get($longKey);

        if (is_array($data) && array_key_exists('cachedContent', $data)) {
            $data['cachedContent'] = unserialize($data['cachedContent']);
            $localCache[$longKey] = $data;
        }

        return $data;
    }

    private function convertReturnData($data, $withMetaData) {

        # do not replace this array_key_exists with isset... as isset will return false for a null value
        if (is_array($data) && array_key_exists('cachedContent', $data)) {
            $returnValue = $withMetaData ? $data : $data['cachedContent'];
        } elseif ($withMetaData) {
            $returnValue = array('generatedDateTime' => new \DateTime(),
                'cachedContentMd5' => md5(serialize($data)),
                'cachedContent' => $data);
        } else {
           $returnValue = $data;
        }

        return $returnValue;
    }

    /*
     * valid parameters:
     *
     *    key                  = this is a unique (and it must be unique) string associated with the cached data you want to store, collisions are devastating so be careful (e.g. REMAINING_KEY_COUNT::DEAL_ID=42, COMMENT_COUNT::NEWS_ID=20 - should be as short as possible but descriptive)
     *    genFunction          = this is the function that you want to call to generate the fresh data if there is a cache miss (e.g. array($this, 'genRemainingKeyCount'), array($instanceOfClass, 'genCommentLis'))) - for more information on this, checkout out the $callback parameter for http://php.net/manual/en/function.call-user-func-array.php
     *    genParameters        = this is the parameter list that you need to pass to the generation function (e.g. array($parameter1, $parameter2,), $aSingleParameterDoesNotRequireArray)
     *    cacheDurationSeconds = this is the amount of time you want the cached item to live for (e.g. 1, 5, 30, 3600)
     *    lockDurationSeconds  = this is the amount of time you want the cache lock to live for (e.g. 1, 5, 30, 3600) - leave this as default, unless you genFunction is going to take a long time to run
     *    hashKey              = this determines whether the key you pass is hashed or not before being used as a key... unless you have a very specific reason for not hashing the key, you should leave this as FALSE (which is the default, so just don't set it), NOTE: all whitespace will be replaced with '_' if this is set to FALSE
     *    siteSpecific         = this allows you to store a specific value for each individual site if required... while true each site will have it's own generated cache data, while false each site will share the cached data
     *    forceGen             = this allows you to generate a fresh version for the cache regardless of whether the data is still valid inside the cache
     *    withMetaData         = this allows you to return extra information about the cached item... this includes a DateTime of when the item was generated as well as a unique md5 hash of the cached content... this is defaulted to false
     */
    public function getOrGen($params) {

        if (empty($params['key'])) {
            $this->logger->err(self::LOG_MESSAGE_PREFIX.'key not defined.');
            throw new CacheFailureException();
        }

        $key = $params['key'];

        if (empty($params['genFunction'])) {
            $this->logger->err(self::LOG_MESSAGE_PREFIX.'genFunction not defined (key="'.$key.'").');
            throw new CacheFailureException();
        }

        $genFunction = $params['genFunction'];

        if (empty($params['genParameters'])) {
            $genParameters = array();
        } else {
            $genParameters = is_array($params['genParameters']) ? $params['genParameters'] : array($params['genParameters']);
        }

        if (empty($params['cacheDurationSeconds']) || $params['cacheDurationSeconds'] < self::MIN_CACHE_DURATION_SECONDS || $params['cacheDurationSeconds'] > self::MAX_CACHE_DURATION_SECONDS) {
            $cacheDurationSeconds = self::DEFAULT_CACHE_DURATION_SECONDS;
        } else {
            $cacheDurationSeconds = $params['cacheDurationSeconds'];
        }

        if (empty($params['lockDurationSeconds']) || $params['lockDurationSeconds'] < self::MIN_LOCK_DURATION_SECONDS || $params['lockDurationSeconds'] > self::MAX_LOCK_DURATION_SECONDS) {
            $lockDurationSeconds = self::DEFAULT_LOCK_DURATION_SECONDS;
        } else {
            $lockDurationSeconds = $params['lockDurationSeconds'];
        }

        $hashKey      = isset($params['hashKey'])       ? (bool) $params['hashKey']      : true;
        $siteSpecific = isset($params['siteSpecific'])  ? (bool) $params['siteSpecific'] : true;
        $withMetaData = isset($params['withMetaData'])  ? (bool) $params['withMetaData'] : false;
        $forceGen     = isset($params['forceGen'])      ? (bool) $params['forceGen']     : false;

        if ($withMetaData && $forceGen) {
            $this->logger->err(self::LOG_MESSAGE_PREFIX.'withMetaData and forceGen are not allowed to be set at the same time... aborting.');
            throw new CacheFailureException();
        }

        if ($siteSpecific && !is_int($this->currentSiteId)) {
            $this->logger->err(self::LOG_MESSAGE_PREFIX.'siteSpecific was set but current site id is not valid.');
            throw new CacheFailureException();
        }

        $longKey = $siteSpecific ? 'SITE_ID='.$this->currentSiteId.'::': '';

        if (!$hashKey) {
            $totalKeyLength = strlen($longKey) + strlen($key) + strlen(self::LOCK_PREFIX_FOR_CACHE_KEYS);

            if ($totalKeyLength > self::MAX_CACHE_KEY_LENGTH) {
                $this->logger->warn(self::LOG_MESSAGE_PREFIX.'hashKey is off and total key length would be longer than MAX_CACHE_KEY_LENGTH... turning hashKey on. key = "'.self::LOCK_PREFIX_FOR_CACHE_KEYS.$longKey.$key.'".');
                $hashKey = true;
            }
        }

        $longKey .= $hashKey ? md5($key) : preg_replace('/\s+/', '_', $key);

        $this->currentLongKey      = $longKey;
        $this->currentAttemptCount = 1;

        if ($this->allowCaching === false) {
            $this->logger->debug(self::LOG_MESSAGE_PREFIX.'Caching is off - generating content.');
            return $this->convertReturnData(call_user_func_array($genFunction, $genParameters), $withMetaData);
        }

        if (isset($this->localCache[$longKey])) {
            $this->logger->debug(self::LOG_MESSAGE_PREFIX.'Item found in local cache - returning.');
            return $this->convertReturnData($this->localCache[$longKey], $withMetaData);
        }

        for ($i = 1; $i <= self::MAX_ATTEMPTS; $i++) {

            $this->logger->debug(self::LOG_MESSAGE_PREFIX.'Attempt number '.$i.' to getOrGen key="'.$longKey.'"');

            $this->currentAttemptCount = $i;

            if (!$forceGen) {
                $data = $this->getFromCache($longKey);

                if ($data) {
                    $this->logger->debug(self::LOG_MESSAGE_PREFIX.'Data found in cache - returning.');
                    return $this->convertReturnData($data, $withMetaData);
                }

                if ($this->cache->getResultCode() !== \Memcached::RES_NOTFOUND) {
                    $this->logger->err(self::LOG_MESSAGE_PREFIX.'Cache did not return the data and cache said "'.$this->cache->getResultCode().'" - going to retry.');
                    sleep(self::SECONDS_BETWEEN_ATTEMPTS);
                    continue;
                }

                $this->logger->debug(self::LOG_MESSAGE_PREFIX.'Cached data not found... going to get locking key.');
            } else {
                $this->logger->debug(self::LOG_MESSAGE_PREFIX.'forceGen is currently on... going to get locking key.');
            }

            $gotLock = $this->getLock($longKey, $lockDurationSeconds);

            if (!$gotLock) {
                sleep(self::SECONDS_BETWEEN_ATTEMPTS);
                continue;
            }

            if (!$forceGen) {

                $data = $this->getFromCache($longKey);

                if ($data) {
                    $this->logger->warn(self::LOG_MESSAGE_PREFIX.'Race condition detected - the cache data exists - deleting locking key and returning cached data. Going to retry.');
                    $this->releaseLastLock();
                    return $this->convertReturnData($data, $withMetaData);
                }
            }

            $this->logger->debug(self::LOG_MESSAGE_PREFIX.'Generating data for cache.');

            $cachedContent           = call_user_func_array($genFunction, $genParameters);
            $serializedCachedContent = serialize($cachedContent);

            $data = array(
                'generatedDateTime' => new \DateTime(),
                'cachedContent'     => $serializedCachedContent,
                'cachedContentMd5'  => md5($serializedCachedContent));

            if (!$forceGen) {

                $result = $this->cache->add($longKey, $data, $cacheDurationSeconds);

                if (!$result) {
                    $this->logger->err(self::LOG_MESSAGE_PREFIX.'Could not store freshly generated data in cache with "add".  Cache said "'.$this->cache->getResultCode().'" - going to try "set".');

                    $result = $this->cache->set($longKey, $data, $cacheDurationSeconds);

                    if (!$result) {
                        $this->logAlertAndThrow('Could not store freshly generated data in cache with "set" (first attempt was "add" which also failed.  Cache said "'.$this->cache->getResultCode().'".');
                    }
                }
            } else {
                $this->cache->set($longKey, $data, $cacheDurationSeconds);
            }

            $data['cachedContent'] = $cachedContent; # this copy of $data was generated and cachedContent was replaced with a serialized version... so we need to reverse that before returning so it can be used...
            $this->localCache[$longKey] = $data; # we usually set localCache when we get a hit from the cache... so given we have generated it freshly here, we need to set it too

            $this->releaseLastLock();
            $this->logger->debug(self::LOG_MESSAGE_PREFIX.'Data generated, set in cache, lock removed and data returned.');

            return $this->convertReturnData($data, $withMetaData);
        }

        $this->logger->err(self::LOG_MESSAGE_PREFIX.'Max attempts reached... we were not able to load the cached data (or generate it successfully).');
        throw new CacheFailureException();
    }

    public function getItem($key)
    {
        $data = $this->getFromCache($key) ? unserialize($data) : null;
    }

    public function getAndDeleteItem($key)
    {
        $data = $this->getFromCache($key);

        if (!$data) {
            return null;
        }

        $this->cache->delete($key);

        return unserialize($data);
    }

    public function addItem($key, $data, $expiry=86400)
    {
        $this->cache->set($key, serialize($data), $expiry);
    }

    public function releaseNamedLock($forKey)
    {
        $lockKeyName = self::LOCK_PREFIX_FOR_CACHE_KEYS.$forKey;
        $lock        = $this->getFromCache($lockKeyName);

        if ($lock === null) {
            $this->logger->warn(self::LOG_MESSAGE_PREFIX.'Was asked to releaseNamedLock but the lock was null.');
            return;
        }

        $this->logger->debug(self::LOG_MESSAGE_PREFIX.'Releasing lock - lock = "'.$lockKeyName.'".');
        $this->cache->delete($lockKeyName);
    }
}
