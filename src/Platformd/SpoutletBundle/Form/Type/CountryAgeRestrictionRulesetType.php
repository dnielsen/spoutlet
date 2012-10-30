<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

use Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRuleset;
use Platformd\SpoutletBundle\Form\Type\CountryAgeRestrictionRuleType;

class CountryAgeRestrictionRulesetType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('rules', 'collection', array(
                'type'          => new CountryAgeRestrictionRuleType(),
                'allow_add'     => true,
                'allow_delete'  => true,
                'label'         => '',
            ));
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_countryagerestrictionruletype';
    }

    private static function getTypeChoices()
    {
        foreach (CountryAgeRestrictionRuleset::getValidParentTypes() as $type) {
            $choices[$type] = $type;
        }

        return $choices;
    }
}
