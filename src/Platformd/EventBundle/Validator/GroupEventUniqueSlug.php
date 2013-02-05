<?php

namespace Platformd\EventBundle\Validator;

/**
 * Special unique validation for "slug" on GroupEvent
 *
 * @Annotation
 */
class GroupEventUniqueSlug extends EventUniqueSlug
{
    /**
     * Validated by GroupEventUniqueSlugValidator
     *
     * @return string
     */
    public function validatedBy()
    {
        return 'platformd.validator.group_event.unique_slug';
    }
}
