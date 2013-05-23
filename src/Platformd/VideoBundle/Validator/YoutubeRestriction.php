<?php

namespace Platformd\VideoBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Special geo restriction validation for YoutubeVideo from YouTube api
 *
 * @Annotation
 */
class YoutubeRestriction extends Constraint
{
    public $message = 'youtube.errors.geo_restriction';

    /**
     * Validated by YoutubeGeoRestrictionValidator
     *
     * @return string
     */
    public function validatedBy()
    {
        return 'platformd.video.youtube.restriction';
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return array(self::CLASS_CONSTRAINT, self::PROPERTY_CONSTRAINT);
    }
}
