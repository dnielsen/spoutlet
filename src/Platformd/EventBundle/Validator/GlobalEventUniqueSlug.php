<?php

namespace Platformd\EventBundle\Validator;

/**
 * @Annotation
 */
class GlobalEventUniqueSlug extends EventUniqueSlug
{
    /**
     * Validated by GroupEventUniqueSlugValidator
     *
     * @return string
     */
    public function validatedBy()
    {
        return 'platformd.validator.global_event.unique_slug';
    }
}
