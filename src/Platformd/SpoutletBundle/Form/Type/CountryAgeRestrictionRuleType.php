<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRule;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CountryAgeRestrictionRuleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('country', EntityType::class, array(
                'label' => 'Country',
                'class' => 'SpoutletBundle:Country',
                'choice_label' => 'name',
                'placeholder' => '',
            ))
            ->add('ruleType', ChoiceType::class, array(
                'choices' => $this->getValidRuleTypes(),
                'label' => 'Allow/Deny',
                'choices_as_values' => true,
            ))
            ->add('minAge', null, array(
                'label' => 'Min Age',
            ))
            ->add('maxAge', null, array(
                'label' => 'Max Age',
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CountryAgeRestrictionRule::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'platformd_spoutletbundle_countryagerestrictionruletype';
    }

    private function getValidRuleTypes()
    {
        $choices = [];

        foreach (CountryAgeRestrictionRule::getValidRuleTypes() as $ruleType) {
            $choices[$ruleType] = $ruleType;
        }

        return $choices;
    }
}
