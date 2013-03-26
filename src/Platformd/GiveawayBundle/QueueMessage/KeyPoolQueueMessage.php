<?php

namespace Platformd\GiveawayBundle\QueueMessage;

class KeyPoolQueueMessage
{
    const QUEUE_NAME = 'LARGE_KEY_UPLOAD';

    public static $classTypeMap = array(
      'GiveawayPool'  => 'GIVEAWAY',
      'DealPool'      => 'DEAL',
    );

    public static $classKeyEntityMap = array(
      'GiveawayPool'  => 'GiveawayKey',
      'DealPool'      => 'DealCode',
    );

    public $bucket;
    public $filename;
    public $siteId;
    public $userId;
    public $poolId;
    public $poolClass;
    public $amazonSqsId;
    public $amazonRecipetHandle;
}
