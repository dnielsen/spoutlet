<?php

namespace Platformd\GiveawayBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Form\Type\CountryRestrictionRulesetType;
use Doctrine\ORM\EntityRepository;

/**
*
*/
class GiveawayPoolType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            // this is a long field, but it's mean for users not to put much here
            ->add('description', 'text', array(
                'required' => false
            ))
            ->add('maxKeysPerIp', 'integer', array(
                'required' => false,
                'label' => 'Max Keys Per IP',
                ))
            ->add('upperLimit', 'integer', array(
                'required' => false,
                'label' => 'Upper Limit',
                ))
            ->add('lowerLimit', 'integer', array(
                'required' => false,
                'label' => 'Lower Limit',
                ))
            ->add('isActive', 'checkbox', array(
                'required' => false,
                'label' => 'Active?',
                ))
            ->add('keysfile', 'file', array(
                'label' => 'Keys File',
            ))
            ->add('ruleset', new CountryRestrictionRulesetType(), array(
                'label' => 'Restrictions',
            ))
            ->add('regions', 'entity', array(
                'class' => 'SpoutletBundle:Region',
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('r')
                        ->andWhere('r.site IS NOT NULL');
                },
                'multiple' => true,
                'expanded' => true,
                'property' => 'name',
                'help' => 'You can choose from the above list of predefined regions, and add additional countries below',
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {

        return 'giveway_pool';
    }
}
