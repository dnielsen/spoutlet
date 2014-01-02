<?php

namespace Platformd\IdeaBundle\Form\Type;

use Symfony\Component\Form\FormBuilder;
use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;
use Platformd\UserBundle\Entity\User;

class RegistrationFormType extends BaseType
{

    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('name')
                ->add('email', 'email')
                ->add('plainPassword', 'repeated', array(
                        'type' => 'password',
                        'invalid_message' => 'passwords_do_not_match',
                        'error_bubbling' => true
                ));
    }

    public function getName()
    {
        return 'idea_user_registration';
    }
}
