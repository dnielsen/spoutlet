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
            ->add('currentPassword', 'password', array(
                'required' => false,
            ))
            ->add('newPassword', 'repeated', array(
                'required' => false,
            ))
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
        ));
    }

    public function getName()
    {
        return 'account_settings';
    }
}

