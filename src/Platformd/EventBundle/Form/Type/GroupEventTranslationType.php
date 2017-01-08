<?php

namespace Platformd\EventBundle\Form\Type;

use Platformd\EventBundle\Entity\GroupEventTranslation;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupEventTranslationType extends EventTranslationType
{
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => GroupEventTranslation::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'platformd_group_event_translation';
    }
}
