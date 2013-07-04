<?php

namespace Platformd\SearchBundle\QueueMessage;

use Platformd\SpoutletBundle\QueueMessage\SqsMessageBase;

class SearchIndexQueueMessage extends SqsMessageBase
{
    const SEARCH_INDEX_S3_PREFIX = 'search_index';

    public $bucket;
    public $filename;

    public function __toString() {
        return 'SearchIndexQueueMessage => { FileName = "'.$this->filename.'" }';
    }

    public function getQueueName() {
        return 'SEARCH_INDEX';
    }
}
