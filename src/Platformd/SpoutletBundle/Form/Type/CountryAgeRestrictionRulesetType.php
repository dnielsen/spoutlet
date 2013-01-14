<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

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

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRuleset',
        );
    }

    private static function getParentTypeChoices()
    {
        foreach (CountryAgeRestrictionRuleset::getValidParentTypes() as $type) {
            $choices[$type] = $type;
        }

        return $choices;
    }

    public function buildViewBottomUp(FormView $view, FormInterface $form)
    {
        parent::buildViewBottomUp($view, $form);

        $view->set('help', '(Optional)<ul>
                            <li>If you add at least one "allow" restriction, everything else is disallowed unless specifically allowed</li>
                            <li>If you only add "disallowed" restrictions, everything else is allowed</li>
                            <li>If there are no restrictions, everyone will be allowed</li>
                            </ul>'
        );
    }
}
