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
                'label'     => '',
                'class'     => 'SpoutletBundle:Country',
                'property'  => 'name',
            ))
            ->add('ruleType', 'choice', array(
                'choices'   => $this->getValidRuleTypes(),
                'label'     => '',
            ))
            ->add('minAge', null, array(
                'label'     => '',
            ))
            ->add('maxAge', null, array(
                'label'     => '',
            ));
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_countryagerestrictionruletype';
    }

    private function getValidRuleTypes()
    {
        foreach (CountryAgeRestrictionRule::getValidRuleTypes() as $ruleType) {
            $choices[$ruleType] = $ruleType;
        }

        return $choices;
    }
}
