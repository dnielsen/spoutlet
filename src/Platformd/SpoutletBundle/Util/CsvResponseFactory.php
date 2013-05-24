<?php

namespace Platformd\SpoutletBundle\Util;

use Symfony\Component\HttpFoundation\Response;

/**
 * Helps convert data into a CSV Response
 */
class CsvResponseFactory
{
    protected $delimiter = ',';
    protected $enclosure = '"';

    private $rows = array();

    public function __construct()
    {

    }

    public function addRow(array $data)
    {
        $this->rows[] = $data;
    }

    /**
     * @param $filename
     */
    public function createResponse($filename)
    {
        $data = $this->generateCsv();

        $response = new Response($data);
        $response->headers->set('Cache-Control', 'public');
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename*=UTF-8''" . urlencode($filename));
        $response->headers->set('Content-Length', strlen($data));

        return $response;
    }

    /**
     * Does the actual work of getting the CSV data
     *
     * It uses a temporary file
     */
    private function generateCsv()
    {
        if (empty($this->rows)) {
            return '';
        }

        // generate the CSV into a file first
        $h = tmpfile();

        $filename = tempnam('/tmp', 'csv_response');
        $h = fopen($filename, 'w'); // TODO use in memory wrapper instead: fopen('php://memory', 'w')

        $bytes = 0;
        foreach ($this->rows as $row) {
            $bytes += fputcsv($h, $row, $this->delimiter, $this->enclosure);
        }
        // close the handle
        fclose($h);

        $contents = file_get_contents($filename);

        unlink($filename);

        return $contents;
    }
}
