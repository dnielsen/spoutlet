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
        $batchCount     = 250;
        $formatString   = '(%s, '.$pool->getId().')';
        $i              = 0;
        $formattedKeys  = array();
        $openFile       = $file->openFile();

        while (!$openFile->eof()) {

            $csvRow = $openFile->fgetcsv();

            if (!$csvRow || empty($csvRow) || trim($csvRow[0]) == "") {
                continue;
            }

            $formattedKeys[] = sprintf($formatString, $this->conn->quote(trim($csvRow[0])));
            $i++;

            if ($i >= $batchCount) {
                $this->executeLoadQuery($formattedKeys, $type);
                $formattedKeys = array();
                $i = 0;
            }
        }

        if (!empty($formattedKeys)) {
            $this->executeLoadQuery($formattedKeys, $type);
        }
    }

    public function loadKeysFromArray($keysArray, $pool, $type = 'GIVEAWAY')
    {
        $batchCount     = 250;
        $formatString   = '(%s, '.$pool->getId().')';
        $i              = 0;
        $formattedKeys  = array();

        foreach ($keysArray as $key) {

            if (!$key || empty($key) || trim($key) == "") {
                continue;
            }

            $formattedKeys[] = sprintf($formatString, $this->conn->quote(trim($key)));
            $i++;

            if ($i >= $batchCount) {
                $this->executeLoadQuery($formattedKeys, $type);
                $formattedKeys = array();
                $i = 0;
            }
        }

        if (!empty($formattedKeys)) {
            $this->executeLoadQuery($formattedKeys, $type);
        }
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
