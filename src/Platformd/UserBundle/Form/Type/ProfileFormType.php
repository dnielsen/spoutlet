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
            ->add('firstname', null, array('required' => true))
            ->add('lastname', null, array('required' => true))
            ->add('file', null, array('required' => false))
            // My rig
            ->add('type', null, array('required' => false))
            ->add('manufacturer', null, array('required' => false))
            ->add('operating_system', null, array('required' => false))
            ->add('cpu', null, array('required' => false))
            ->add('memory', null, array('required' => false))
            ->add('video_card', null, array('required' => false))
            ->add('sound_card', null, array('required' => false))
            ->add('hard_drive', null, array('required' => false))
            ->add('headphones', null, array('required' => false))
            ->add('mouse', null, array('required' => false))
            ->add('mousepad', null, array('required' => false))
            ->add('keyboard', null, array('required' => false))
            ->add('monitor', null, array('required' => false));
    }
}
