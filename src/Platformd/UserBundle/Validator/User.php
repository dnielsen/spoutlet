<?php

namespace Platformd\UserBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Custom validation for the User entity
 *
 * @Annotation
 */
class User extends Constraint
{
    public $message = 'This value is incorrect';

    public function validatedBy()
    {
        return 'platformd.validator.user.user';
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
