<?php

namespace Platformd\EventBundle\Form\Type;

use Platformd\EventBundle\Entity\GlobalEventTranslation;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class GlobalEventTranslationType extends EventTranslationType
{
    public function getName()
    {
        return 'platformd_global_event_translation';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => GlobalEventTranslation::class,
        ]);
    }
}
