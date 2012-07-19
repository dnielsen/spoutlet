<?php

namespace Knp\MediaBundle\Exception;

use LogicException;
use Exception;

/**
 * Thrown when there is a problem trying to generate a unique filename for an upload
 */
class UniqueFilenameGenerationException extends LogicException
{
    private $filename;

    public function __construct($filename, $code = 0, Exception $previous = null)
    {
        $this->filename = $filename;
        $message = sprintf('Could not generate a unique filename for "%s"', $filename);

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }
}