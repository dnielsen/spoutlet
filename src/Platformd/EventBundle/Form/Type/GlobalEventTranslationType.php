<?php

namespace Platformd\EventBundle\Form\Type;

use Platformd\EventBundle\Entity\GlobalEventTranslation;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GlobalEventTranslationType extends EventTranslationType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => GlobalEventTranslation::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'platformd_global_event_translation';
    }
}
