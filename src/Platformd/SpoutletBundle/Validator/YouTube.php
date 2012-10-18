<?php

namespace Platformd\SpoutletBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
* @Annotation
*/
class YouTube extends Constraint
{
    public $message = 'Please provide a valid YouTube video ID.';
}
