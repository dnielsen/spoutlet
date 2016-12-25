<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

use Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRuleset;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CountryAgeRestrictionRulesetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('rules', 'collection', array(
                'type' => new CountryAgeRestrictionRuleType(),
                'allow_add' => true,
                'allow_delete' => true,
                'label' => '',
            ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CountryAgeRestrictionRuleset::class,
        ]);
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_countryagerestrictionruletype';
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
