<?php

namespace Platformd\MediaBundle\Form\Type;

use Knp\MediaBundle\Form\Type\MediaType as BaseMediaType;

class MediaType extends BaseMediaType
{
    /**
     * @return string
     */
    public function getDataClass()
    {
        return 'Platformd\MediaBundle\Entity\Media';
    }

}
