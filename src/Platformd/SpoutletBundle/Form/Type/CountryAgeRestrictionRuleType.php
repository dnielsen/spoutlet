<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

use Platformd\SpoutletBundle\Entity\Country;
use Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRule;

class CountryAgeRestrictionRuleType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('country', 'entity', array(
                'label'     => 'Country',
                'class'     => 'SpoutletBundle:Country',
                'property'  => 'name',
            ))
            ->add('ruleType', 'choice', array(
                'choices'   => $this->getValidRuleTypes(),
                'label'     => 'Allow/Deny',
            ))
            ->add('minAge', null, array(
                'label'     => 'Min Age',
            ))
            ->add('maxAge', null, array(
                'label'     => 'Max Age',
            ));
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_countryagerestrictionruletype';
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
