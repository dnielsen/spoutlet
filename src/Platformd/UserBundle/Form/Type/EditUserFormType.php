<?php

namespace Platformd\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditUserFormType extends AbstractType
{
    const ROLES = [
        'No admin' => '',
        'Limited admin' => 'ROLE_ORGANIZER',
        'Full admin' => 'ROLE_SUPER_ADMIN',
        'Dell Contact' => 'ROLE_PARTNER',
        'Japan Regional Admin' => 'ROLE_JAPAN_ADMIN',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options)
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
                'choices' => self::ROLES,
                'choices_as_values' => true,
                'label' => 'Admin Level',
            ));
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'allow_promote' => false,
            'local_auth' => false,
            'validation_groups' => ['AdminEdit'],
        ]);
    }

    public function getName()
    {
        return 'fos_user_profile_form_user';
    }
}
