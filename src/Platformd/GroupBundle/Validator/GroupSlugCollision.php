<?php

namespace Platformd\GroupBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class GroupSlugCollision extends Constraint
{
    public $message         = 'Please choose different URL text:';
    public $fieldMessage    = 'Please specify different URL text for your group - the one chosen or automatically generated is not allowed.';

    /**
     * Validated by GroupSlugCollisionValidator
     *
     * @return string
     */
    public function validatedBy()
    {
        return 'platformd.validator.group.slug_collision';
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
