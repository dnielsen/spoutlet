<?php

namespace Platformd\EventBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class EventFindType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('eventName', 'text', array(
                'label' => 'Name:'
            ))
            ->add('published', 'choice', array(
                'label' => 'Status:',
                'choices' => array(
                    '1' => 'Active',
                    '0' => 'Inactive'
                ),
                'empty_value' => 'Select All',
                'required' => false,
            ))
            ->add('eventType', 'choice', array(
                'label' => 'Type',
                'choices' => array(
                    'group' => 'Group',
                    'global' => 'Global'
                )
            ))
            ->add('sites', 'entity', array(
                'class'    => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'property' => 'name',
            ))
            ->add('from', 'date', array(
                'label' => 'Starts After:',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr'   => array(
                    'class' => 'date-picker'
                )
            ))
            ->add('thru', 'date', array(
                'label' => 'Starts Before:',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr'   => array(
                    'class' => 'date-picker'
                )
            ));
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'csrf_protection' => false,
        );
    }

    public function getName()
    {
        return 'platformd_eventbundle_eventfindtype';
    }

}
