<?php

namespace Platformd\UserBundle\Form\Type;

use Symfony\Component\Form\FormBuilder;

use FOS\UserBundle\Form\Type\ProfileFormType as BaseProfileFormType;

class ProfileFormType extends BaseProfileFormType
{

    protected function buildUserForm(FormBuilder $builder, array $options)
    {
        parent::buildUserForm($builder, $options);

        $builder
            ->add('firstname')
            ->add('lastname')
            ->add('file')
            // My rig
            ->add('type')
            ->add('manufacturer')
            ->add('operating_system')
            ->add('cpu')
            ->add('memory')
            ->add('video_card')
            ->add('sound_card')
            ->add('hard_drive')
            ->add('headphones')
            ->add('mouse')
            ->add('mousepad')
            ->add('keyboard')
            ->add('monitor');
    }
}
