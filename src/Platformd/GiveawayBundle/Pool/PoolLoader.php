<?php

namespace Platformd\GiveawayBundle\Pool;

use Symfony\Component\HttpFoundation\File\File;
use Platformd\GiveawayBundle\Entity\GiveawayPool;
use Doctrine\DBAL\Connection;

/**
 * Utility function for adding
 */
class PoolLoader
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Opens the CSV-formatted file and adds each key to the given GiveawayPool
     *
     * @param \Symfony\Component\HttpFoundation\File\File $file
     * @param \Platformd\GiveawayBundle\Entity\GiveawayPool $pool
     */
    public function loadKeysFromFile(File $file, GiveawayPool $pool)
    {
        $content = $file->openFile();
        $limit = 1000;

        $valuesString = array();
        $i = 0;
        while (!$content->eof()) {
            $value = $content->fgets();
            if (!$value || empty($value)) {
                continue;
            }

            // remove the trailing line break
            $value = str_replace("\n", '', $value);

            // create the little part of the insert string
            // optimized for speed - but ugly...
            $valuesString[] = sprintf("('%s', %s)", mysql_real_escape_string($value), $pool->getId());

            $i++;

            if (0 == ($i % $limit)) {
                $this->executeLoadQuery($valuesString);
                $valuesString = array();
            }
        }

        // execute everything else
        $this->executeLoadQuery($valuesString);


    }

    private function executeLoadQuery(array $valuesString)
    {
        if (empty($valuesString)) {
            return;
        }

        $query = sprintf('INSERT INTO giveaway_key (value, pool) VALUES %s', implode(', ', $valuesString));
        $stmt = $this->conn->prepare($query);

        $stmt->execute();
    }
}