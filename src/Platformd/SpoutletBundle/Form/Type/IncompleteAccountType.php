<?php

namespace Platformd\SpoutletBundle\Form\Type;

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
                'label'             => 'platformd.account_page.incomplete_form.username',
                'required'          => true,
            ))
            ->add('firstname', null, array(
                'label'             => 'platformd.account_page.incomplete_form.first_name',
                'required'          => true,
            ))
            ->add('lastname', null, array(
                'label'             => 'platformd.account_page.incomplete_form.last_name',
                'required'          => true,
            ))
            ->add('email', 'text', array(
                'label'             => 'platformd.account_page.incomplete_form.email',
                'required'          => true,
            ))
            ->add('plainPassword', 'repeated', array(
                'label'             => 'platformd.account_page.incomplete_form.password',
                'type'              => 'password',
                'required'          => true,
                'error_bubbling'    => true,
                'invalid_message'   => 'passwords_do_not_match',
            ))
            ->add('hasAlienwareSystem', 'choice', array(
                'expanded'          => true,
                'choices'           => array(1 => 'Yes', 0 => 'No'),
                'required'          => true,
                'label'             => 'platformd.account_page.incomplete_form.has_alienware',
            ))
            ->add('subscribedGamingNews', null, array(
                'label' => 'platformd.account_page.incomplete_form.subscribe_me',
            ))
            ->add('termsAccepted', 'checkbox', array(
                'label'             => 'platformd.account_page.incomplete_form.agree_to_terms',
                'required'          => true,
            ))
        ;
    }

    public function getName()
    {

        return 'platformd_incomplete_account';
    }
}
