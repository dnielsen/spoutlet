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
            ->add('file');
    }
}
