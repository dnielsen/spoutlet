<?php

namespace Platformd\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class EditUserFormType extends AbstractType
{
    
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('username')
            ->add('firstname')
            ->add('lastname')
            ->add('email');
        
        if ($options['allow_promote']) {
            $builder->add('admin_level', 'choice', array(
                'choices' => array(
                    'ROLE_ORGANIZER' => 'Content Admin',
                    'ROLE_SUPER_ADMIN' => 'Super admin',
                ),
                'empty_value' => 'Choose an admin level',
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
            'allow_promote' => false
        ));
    }

}

