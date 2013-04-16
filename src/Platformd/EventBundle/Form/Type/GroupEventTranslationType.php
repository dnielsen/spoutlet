<?php

namespace Platformd\EventBundle\Form\Type;

class GroupEventTranslationType extends EventTranslationType
{
    public function getName()
    {
        return 'platformd_group_event_translation';
    }

    public function getDefaultOptions(array $options)
    {
        $options['data_class'] = 'Platformd\EventBundle\Entity\GroupEventTranslation';

        return $options;
    }
}
