<?php

namespace Platformd\EventBundle\Form\Type;

class GlobalEventTranslationType extends EventTranslationType
{
    public function getName()
    {
        return 'platformd_global_event_translation';
    }

    public function getDefaultOptions(array $options)
    {
        $options['data_class'] = 'Platformd\EventBundle\Entity\GlobalEventTranslation';

        return $options;
    }
}
