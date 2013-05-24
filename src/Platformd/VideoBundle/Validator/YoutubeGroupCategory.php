<?php

namespace Platformd\VideoBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Special geo restriction validation for YoutubeVideo from YouTube api
 *
 * @Annotation
 */
class YoutubeGroupCategory extends Constraint
{
    public $message = 'youtube.errors.no_gallery';

    /**
     * Validated by YoutubeGeoRestrictionValidator
     *
     * @return string
     */
    public function validatedBy()
    {
        return 'platformd.video.youtube.gallery_groups';
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return array(self::CLASS_CONSTRAINT);
    }
}
