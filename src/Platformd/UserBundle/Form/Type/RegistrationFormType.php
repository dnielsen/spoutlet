<?php

namespace Platformd\UserBundle\Form\Type;

use Symfony\Component\Form\FormBuilder;
use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;

class RegistrationFormType extends BaseType
{
    
    public function buildForm(FormBuilder $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('firstname')
            ->add('lastname')
            ->add('birthdate', 'date')
            ->add('phoneNumber')
            ->add('country', 'country')
            ->add('state')
            ->add('hasAlienwareSystem', 'checkbox')
            ->add('hasAlienwareSystem')
            ->add('latestNewsSource')
            ->add('subscribedArenaNews')
            ->add('subscribedGamingNews')
            ->add('termsAccepted');

    }

    public function getName()
    {
        
        return 'patformd_user_registration';
    }
}
