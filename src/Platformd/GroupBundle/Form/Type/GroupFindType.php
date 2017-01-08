<?php

namespace Platformd\GroupBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class GroupFindType extends AbstractType
{
    const STATUSES = [
        'Active' => 0,
        'Inactive' => 1,
    ];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('groupName', 'text', array(
                'label' => 'Name:'
            ))
            ->add('deleted', 'choice', array(
                'label' => 'Status:',
                'choices' => self::STATUSES,
                'choices_as_values' => true,
                'empty_value' => 'Select All',
                'required' => false,
            ))
            ->add('category', 'choice', array(
                'label' => 'Category:',
                'choices' => array(
                    'location' => 'Location',
                    'topic' => 'Topic'
                ),
                'choices_as_values' => true,
                'empty_value' => 'Select All',
                'required' => false,
            ))
            ->add('sites', 'entity', array(
                'class'    => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name',
            ))
            ->add('startDate', 'date', array(
                'label' => 'Start Date:',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr'   => array(
                    'class' => 'date-picker'
                )
            ))
            ->add('endDate', 'date', array(
                'label' => 'End Date:',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr'   => array(
                    'class' => 'date-picker'
                )
            ));
    }

    public function getName()
    {
        return 'platformd_groupbundle_groupfindtype';
    }
}
