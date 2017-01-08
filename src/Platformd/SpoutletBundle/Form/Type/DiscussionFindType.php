<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class DiscussionFindType extends AbstractType
{
    const STATUSES = [
        'Active' => 0,
        'Inactive' => 1,
    ];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('discussionName', 'text', array(
                'label' => 'Name:'
            ))
            ->add('deleted', 'choice', array(
                'label' => 'Status:',
                'choices' => self::STATUSES,
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
            ->add('from', 'date', array(
                'label' => 'Start Date:',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr'   => array(
                    'class' => 'date-picker'
                )
            ))
            ->add('thru', 'date', array(
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
        return 'platformd_spoutletbundle_discussionfindtype';
    }
}
