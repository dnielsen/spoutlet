<?php

namespace Platformd\SpoutletBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Platformd\SpoutletBundle\Entity\RsvpCode;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\HttpFoundation\File\File;

class CsvToRsvpCodeTransformer implements DataTransformerInterface
{
    private $file;

    public function transform($value)
    {
        if ($this->file instanceof File) {
            return $this->file->getFilename();
        }

        return;

        // useless
        if (null === $value) {
            return;
        }

        if ($value instanceof RsvpCode) {
            return $value->getValue();
        }

        throw new UnexpectedTypeException($value, 'RsvpCode');
    }

    public function reverseTransform($value)
    {
        if (empty($value)) {
            return array();
        }
        $this->file = $value;

        $csvFile = new \SplFileObject($value);

        if (!in_array($value->getMimeType(), array('application/vnd.ms-excel','text/plain','text/csv','text/tsv'))) {
            return false;
        }

        $csvFile->setFlags(\SplFileObject::READ_CSV);

        $codes = array();
        foreach ($csvFile as $row) {
            if (empty($row[0])) {
                continue;
            }
            $code = new RsvpCode($row[0]);

            $codes[] = $code;
        }

        return $codes;
    }
}

