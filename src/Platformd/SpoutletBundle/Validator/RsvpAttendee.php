<?php

namespace Platformd\SpoutletBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
* @Annotation
*/
class RsvpAttendee extends Constraint
{
    public $message = 'This RSVP code is already registered.';

    public function validatedBy()
    {
        return 'platformd.validator.rsvp_attendee';
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
