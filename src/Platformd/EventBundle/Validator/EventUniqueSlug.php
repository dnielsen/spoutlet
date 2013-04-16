<?php

namespace Platformd\EventBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Special unique validation for "slug" on event
 *
 * @Annotation
 */
class EventUniqueSlug extends Constraint
{
    public $message = 'Please choose another URL string - this one is not unique within the system.';

    /**
     * Validated by EventUniqueSlugValidator
     *
     * @return string
     */
    public function validatedBy()
    {
        return 'platformd.validator.event.unique_slug';
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
