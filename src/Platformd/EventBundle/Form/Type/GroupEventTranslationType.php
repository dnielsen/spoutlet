<?php

namespace Platformd\EventBundle\Form\Type;

use Platformd\EventBundle\Entity\GroupEventTranslation;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupEventTranslationType extends EventTranslationType
{
    public function getName()
    {
        return 'platformd_group_event_translation';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => GroupEventTranslation::class,
        ]);
    }
}
