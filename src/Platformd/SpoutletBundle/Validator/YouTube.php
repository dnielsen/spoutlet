<?php

namespace Platformd\SpoutletBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
* @Annotation
*/
class YouTube extends Constraint
{
    public $message = 'The YouTube ID you entered is invalid.';
}
