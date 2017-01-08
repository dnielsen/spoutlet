<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

use Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRuleset;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CountryRestrictionRulesetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('rules', 'collection', array(
                'type' => new CountryRestrictionRuleType(),
                'allow_add' => true,
                'allow_delete' => true,
                'label' => '',
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CountryAgeRestrictionRuleset::class,
        ]);
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_countryagerestrictionruletype';
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars = array_replace($view->vars, [
            'help' => '(Optional)<ul>
                            <li>If you add at least one "allow" restriction, everything else is disallowed unless specifically allowed</li>
                            <li>If you only add "disallowed" restrictions, everything else is allowed</li>
                            <li>If there are no restrictions, everyone will be allowed</li>
                            </ul>',
        ]);
    }
}
