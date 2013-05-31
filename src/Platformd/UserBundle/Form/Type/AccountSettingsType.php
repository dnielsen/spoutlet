<?php

namespace Platformd\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Platformd\UserBundle\Form\Type\UserAvatarType;
use Symfony\Component\Form\FormBuilder;

class AccountSettingsType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('userAvatars', 'collection', array(
                'type'         => new UserAvatarType,
                'allow_add'    => true,
                'allow_delete' => true,
                'by_reference' => false,
            ))
        ;
    }

    public function getDefaultOptions(array $options)
    {
        return array_merge($options, array(
            'data_class' => 'Platformd\UserBundle\Entity\User',
            'validation_groups' => array('Default', 'Avatar')
        ));
    }

    public function getName()
    {
        return 'account_settings';
    }
}

