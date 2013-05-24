<?php

namespace Platformd\HtmlWidgetBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Special unique validation for "slug" on html widgets
 *
 * @Annotation
 */
class HtmlWidgetSlug extends Constraint
{
    public $message = 'Please choose another URL string - this one is not unique within the system for the sites on which it is enabled.';

    public function validatedBy()
    {
        return 'platformd.validator.html_widget.slug';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
