<?php

namespace Platformd\SpoutletBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Special unique validation for "slug" on abstract event
 *
 * This is tricky because the slug has to be unique with respect to:
 *
 *      * The locale column
 *      * The event "type" or discr column
 *
 * @Annotation
 */
class AbstractEventUniqueSlug extends Constraint
{
    public $message = 'Please choose another URL string - this one is not unique within the system.';

    /**
     * Validated by AbstractEventUniqueSlugValidator
     *
     * @return string
     */
    public function validatedBy()
    {
        return 'abstract_event.unique_slug';
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}