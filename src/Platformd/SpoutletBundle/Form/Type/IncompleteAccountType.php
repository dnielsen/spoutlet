<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

class IncompleteAccountType extends AbstractType
{
    private $siteUtil;

    public function __construct($siteUtil)
    {
        $this->siteUtil = $siteUtil;
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        $config            = $this->siteUtil->getCurrentSite()->getSiteConfig();
        $birthdateRequired = $config->getBirthdateRequired();
        $user              = $builder->getData();

        if (!$user->getUsername()) {
            $builder->add('username', null, array(
                'label'    => 'platformd.account_page.incomplete_form.username',
                'required' => true,
            ));
        }

        if (!$user->getFirstname()) {
            $builder->add('firstname', null, array(
                'label'    => 'platformd.account_page.incomplete_form.first_name',
                'required' => true,

            ));
        }

        if (!$user->getLastname()) {
            $builder->add('lastname', null, array(
                'label'    => 'platformd.account_page.incomplete_form.last_name',
                'required' => true,
            ));
        }

        if (!$user->getEmail()) {
                $builder->add('email', 'text', array(
                'label'             => 'platformd.account_page.incomplete_form.email',
                'required'          => true,
            ));
        }

        if (!$user->getPassword()) {
            $builder->add('plainPassword', 'repeated', array(
                'label'           => 'platformd.account_page.incomplete_form.password',
                'type'            => 'password',
                'required'        => true,
                'invalid_message' => 'passwords_do_not_match',
            ));
        }

        if ($user->getHasAlienwareSystem() === null) {
            $builder->add('hasAlienwareSystem', 'choice', array(
                'expanded' => true,
                'choices'  => array(1 => 'Yes', 0 => 'No'),
                'required' => true,
                'label'    => 'platformd.account_page.incomplete_form.has_alienware',
            ));
        }

        if (!$user->getSubscribedAlienwareEvents()) {
            $builder->add('subscribedAlienwareEvents', null, array(
                'label' => 'platformd.account_page.incomplete_form.subscribe_alienware_arena',
            ));
        }

        if (!$user->getSubscribedGamingNews()) {
            $builder->add('subscribedGamingNews', null, array(
                'label' => 'platformd.account_page.incomplete_form.subscribe_dell',
            ));
        }

        if (!$user->getTermsAccepted()) {
            $builder->add('termsAccepted', 'checkbox', array(
                'label'    => 'platformd.account_page.incomplete_form.agree_to_terms',
                'required' => true,
                'error_bubbling' => true,
            ));
        }

        if (!$user->getCountry()) {
            $builder->add('termsAccepted', 'checkbox', array(
                'label'    => 'platformd.account_page.incomplete_form.agree_to_terms',
                'required' => true,
                'error_bubbling' => true,
            ));
        }

        if (!$user->getState()) {
            $builder->add('termsAccepted', 'checkbox', array(
                'label'    => 'platformd.account_page.incomplete_form.agree_to_terms',
                'required' => true,
                'error_bubbling' => true,
            ));
        }

        if($birthdateRequired && !$user->getBirthdate()) {
            $builder->add('birthdate', 'birthday', array(
                'empty_value' => '--', 'required' => true,
                'years' => range(1940, date('Y')),
            ));
        }
    }

    public function getName()
    {
        return 'platformd_incomplete_account';
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'validation_groups' => array('IncompleteUser', 'Default')
        );
    }
}
