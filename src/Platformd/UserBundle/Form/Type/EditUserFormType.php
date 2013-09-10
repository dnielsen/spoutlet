<?php

namespace Platformd\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class EditUserFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('username', null, array('label' => 'Username'))
            ->add('email', 'email')
            ->add('firstname', null, array('label' => 'First Name'))
            ->add('lastname', null, array('label' => 'Last Name'));


        if ($options['local_auth']) {
            $builder
                ->add('birthdate', 'birthday', array(
                    'empty_value' => '',
                    'attr' => array(
                        'class' => 'birthday',
                    ),
                ))
                ->add('phoneNumber', null, array('label' => 'Phone Number'))
                ->add('country', 'country')
                ->add('state')
            ;
        }

        if ($options['allow_promote']) {
            $builder->add('admin_level', 'choice', array(
                // these are supported by a number of odd methods in User
                'choices' => array(
                    '' => 'No admin',
                    'ROLE_ORGANIZER' => 'Limited admin',
                    'ROLE_SUPER_ADMIN' => 'Full admin',
                    'ROLE_PARTNER'   => 'Dell Contact',
                    'ROLE_JAPAN_ADMIN' => 'Japan Regional Admin',
                ),
                'label' => 'Admin Level',
            ));
        }
    }

    public function getName()
    {
        return 'fos_user_profile_form_user';
    }

    public function getDefaultOptions(array $options)
    {
        return array_merge($options, array(
            'allow_promote' => false,
            'local_auth' => false,
            'validation_groups' => array('AdminEdit')
        ));
    }

}

