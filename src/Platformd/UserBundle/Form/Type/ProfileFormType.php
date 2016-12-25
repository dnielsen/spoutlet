<?php

namespace Platformd\UserBundle\Form\Type;

use FOS\UserBundle\Form\Type\ProfileFormType as BaseProfileFormType;
use Symfony\Component\Form\FormBuilderInterface;

class ProfileFormType extends BaseProfileFormType
{
    protected function buildUserForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildUserForm($builder, $options);

        $builder
            ->add('firstname', null, array('required' => true))
            ->add('lastname', null, array('required' => true))
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
