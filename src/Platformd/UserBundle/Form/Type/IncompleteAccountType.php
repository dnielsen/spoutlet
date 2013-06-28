<?php

namespace Platformd\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

class IncompleteAccountType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('username', null, array(
                'label'             => '',
                'required'          => true,
            ))
            ->add('firstname', null, array(
                'label'             => '',
                'required'          => true,
            ))
            ->add('lastname', null, array(
                'label'             => '',
                'required'          => true,
            ))
            ->add('email', 'text', array(
                'label'             => '',
                'required'          => true,
            ))
            ->add('plainPassword', 'repeated', array(
                'type'              => 'password',
                'required'          => true,
            ))
            ->add('hasAlienwareSystem', 'choice', array(
                'expanded'          => true,
                'choices'           => array(1 => 'Yes', 0 => 'No'),
                'required'          => true,
            ))
            ->add('subscribedGamingNews')
            ->add('termsAccepted', 'checkbox', array(
                'required'          => true,
            ))
        ;
    }

    public function getName()
    {

        return 'platformd_incomplete_account';
    }
}
