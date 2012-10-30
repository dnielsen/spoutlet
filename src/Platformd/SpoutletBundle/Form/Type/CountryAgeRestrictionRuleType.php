<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

use Platformd\SpoutletBundle\Entity\Country;

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
                'choices'   => array(1 => 'Allow', 2 => 'Disallow'),
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
}
