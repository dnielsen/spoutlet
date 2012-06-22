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
    public function loadKeysFromFile(File $file, $pool, $type = 'GIVEAWAY')
    {
        $content = $file->openFile();
        $limit = 1000;

        $valuesString = array();
        $i = 0;
        $formatString = '("%s", '.$pool->getId().')';

        while (!$content->eof()) {

            $value = $content->fgets();

            if (!$value || empty($value)) {
                continue;
            }

            $valuesString[] = sprintf($formatString, $this->conn->quote(trim($value)));

            $i++;

            if (0 == ($i % $limit)) {
                $this->executeLoadQuery($valuesString, $type);
                $valuesString = array();
            }
        }

        // execute everything else
        $this->executeLoadQuery($valuesString, $type);
    }

    private function executeLoadQuery(array $valuesString, $type)
    {
        if (empty($valuesString)) {
            return;
        }

        switch ($type) {
            case 'GIVEAWAY':
                $tableName = 'giveaway_key';
                break;
            case 'DEAL':
                $tableName = 'deal_code';
                break;
            default:
                throw new \Exception('Unknown table type.');
        }

        $query = sprintf('INSERT INTO `%s` (value, pool) VALUES %s', $tableName, implode(', ', $valuesString));
        $stmt = $this->conn->prepare($query);

        $stmt->execute();
    }
}
