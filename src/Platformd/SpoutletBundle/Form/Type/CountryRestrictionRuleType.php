<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRule;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CountryRestrictionRuleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('country', 'entity', array(
                'label'     => 'Country',
                'class'     => 'SpoutletBundle:Country',
                'choice_label'  => 'name',
                'empty_value' => '',
            ))
            ->add('ruleType', 'choice', array(
                'choices'   => $this->getValidRuleTypes(),
                'label'     => 'Allow/Deny',
                'choices_as_values' => true,
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CountryAgeRestrictionRule::class,
        ]);
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_countryrestrictionruletype';
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
