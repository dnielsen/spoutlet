<?php

namespace Platformd\UserBundle\Form\Type;

use FOS\UserBundle\Form\Type\ResettingFormType as BaseType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;

class ResettingFormType extends BaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('new', RepeatedType::class, array('type' => PasswordType::class, 'invalid_message' => 'passwords_do_not_match'));
    }

    public function getBlockPrefix()
    {
        return 'platformd_user_resetting';
    }
}
