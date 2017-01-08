<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncompleteAccountType extends AbstractType
{
    const YES_NO = [
        'Yes' => 1,
        'No' => 0,
    ];

    private $siteUtil;

    public function __construct($siteUtil)
    {
        $this->siteUtil = $siteUtil;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $config = $this->siteUtil->getCurrentSite()->getSiteConfig();
        $birthdateRequired = $config->getBirthdateRequired();
        $user = $builder->getData();

        if (!$user->getUsername()) {
            $builder->add('username', null, array(
                'label' => 'platformd.account_page.incomplete_form.username',
                'required' => true,
            ));
        }

        if (!$user->getFirstname()) {
            $builder->add('firstname', null, array(
                'label' => 'platformd.account_page.incomplete_form.first_name',
                'required' => true,

            ));
        }

        if (!$user->getLastname()) {
            $builder->add('lastname', null, array(
                'label' => 'platformd.account_page.incomplete_form.last_name',
                'required' => true,
            ));
        }

        if (!$user->getEmail()) {
            $builder->add('email', TextType::class, array(
                'label' => 'platformd.account_page.incomplete_form.email',
                'required' => true,
            ));
        }

        if (!$user->getPassword()) {
            $builder->add('plainPassword', RepeatedType::class, array(
                'label' => 'platformd.account_page.incomplete_form.password',
                'type' => 'password',
                'required' => true,
                'invalid_message' => 'passwords_do_not_match',
            ));
        }

        if ($user->getHasAlienwareSystem() === null) {
            $builder->add('hasAlienwareSystem', ChoiceType::class, array(
                'expanded' => true,
                'choices' => self::YES_NO,
                'choices_as_values' => true,
                'required' => true,
                'label' => 'platformd.account_page.incomplete_form.has_alienware',
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
            $builder->add('termsAccepted', CheckboxType::class, array(
                'label' => 'platformd.account_page.incomplete_form.agree_to_terms',
                'required' => true,
                'error_bubbling' => true,
            ));
        }

        if (!$user->getCountry()) {
            $builder->add('termsAccepted', CheckboxType::class, array(
                'label' => 'platformd.account_page.incomplete_form.agree_to_terms',
                'required' => true,
                'error_bubbling' => true,
            ));
        }

        if (!$user->getState()) {
            $builder->add('termsAccepted', CheckboxType::class, array(
                'label' => 'platformd.account_page.incomplete_form.agree_to_terms',
                'required' => true,
                'error_bubbling' => true,
            ));
        }

        if ($birthdateRequired && !$user->getBirthdate()) {
            $builder->add('birthdate', BirthdayType::class, array(
                'empty_value' => '--', 'required' => true,
                'years' => range(1940, date('Y')),
            ));
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => array('IncompleteUser'),
        ]);
    }

    public function getBlockPrefix()
    {
        return 'platformd_incomplete_account';
    }
}
