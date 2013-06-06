<?php

namespace Platformd\SpoutletBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueBackgroundAdPerTime extends Constraint
{
    public $message = 'Error! This schedule conflicts with another banner that is scheduled at the same time. Please uncheck the conflicting site.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy()
    {
        return 'unique_background_ad_time_per_site';
    }
}

