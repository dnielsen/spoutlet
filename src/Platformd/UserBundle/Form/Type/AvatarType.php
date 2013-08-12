<?php

namespace Platformd\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\MediaBundle\Form\Type\MediaType;

class AvatarType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('file', 'file', array(
                'label' => 'platformd.user.avatars.file_label',
                'required' => false,
            ))
        ;
    }

    public function getName()
    {
        return 'platformd_userbundle_avatartype';
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Platformd\UserBundle\Entity\Avatar',
        );
    }
}
