<?php

namespace Platformd\EventBundle\Form\Type;

use Platformd\EventBundle\Entity\GroupEventTranslation;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class GroupEventTranslationType extends EventTranslationType
{
    public function getName()
    {
        return 'platformd_group_event_translation';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => GroupEventTranslation::class,
        ]);
    }
}
