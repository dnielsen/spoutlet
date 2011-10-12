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
    }

    public function getName()
    {
        
        return 'fos_user_profile_form_user';
    }

}

