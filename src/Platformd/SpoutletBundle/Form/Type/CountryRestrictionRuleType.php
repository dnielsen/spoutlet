<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

use Platformd\SpoutletBundle\Entity\Country;
use Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRule;

class CountryRestrictionRuleType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('country', 'entity', array(
                'label'     => 'Country',
                'class'     => 'SpoutletBundle:Country',
                'property'  => 'name',
                'empty_value' => '',
            ))
            ->add('ruleType', 'choice', array(
                'choices'   => $this->getValidRuleTypes(),
                'label'     => 'Allow/Deny',
            ));
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_countryrestrictionruletype';
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRule',
        );
    }

    private function getValidRuleTypes()
    {
        foreach (CountryAgeRestrictionRule::getValidRuleTypes() as $ruleType) {
            $choices[$ruleType] = $ruleType;
        }

        return $choices;
    }
}
