<?php

namespace Platformd\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\MediaBundle\Form\Type\MediaType;

class UserAvatarType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('media', new MediaType, array(
                'with_remove_checkbox' => false,
            ))
        ;
    }

    public function getName()
    {
        return 'user_avatar';
    }

    public function getDefaultOptions(array $options)
    {
        return array_merge($options, array(
            'data_class' => 'Platformd\UserBundle\Entity\UserAvatar',
        ));
    }
}

