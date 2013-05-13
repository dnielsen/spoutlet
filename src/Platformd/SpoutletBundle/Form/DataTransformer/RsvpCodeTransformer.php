<?php

namespace Platformd\SpoutletBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Platformd\SpoutletBundle\Entity\RsvpCode;
use Symfony\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class RsvpCodeTransformer implements DataTransformerInterface
{
    private $doctrine;

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function transform($value)
    {
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
        if (null === $value) {
            return;
        }

        $codes = $this->doctrine->getRepository('SpoutletBundle:RsvpCode')->findBy(array(
            'value' => $value,
        ));

        $assignedIds = $this->doctrine->getRepository('SpoutletBundle:RsvpAttendee')->findAssignedCodesWithValue($value);

        $code = null;

        foreach ($codes as $matchedCode) {
            if (!in_array($matchedCode->getId(), $assignedIds)) {
                $code = $matchedCode;
                break;
            }

            // set code to already assigned code as fallback - this is checked in the validator and shows error if code is already assigned.
            $code = $matchedCode;
        }

        return $code;
    }
}

