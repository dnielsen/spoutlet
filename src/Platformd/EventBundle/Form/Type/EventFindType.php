<?php

namespace Platformd\EventBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventFindType extends AbstractType
{
    const STATUS = [
        'Active' => 1,
        'Inactive' => 0,
    ];

    const EVENT_TYPES = [
        'Group' => 'group',
        'Global' => 'global',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('eventName', 'text', array(
                'label' => 'Name:'
            ))
            ->add('published', 'choice', array(
                'label' => 'Status:',
                'choices' => self::STATUS,
                'choices_as_values' => true,
                'empty_value' => 'Select All',
                'required' => false,
            ))
            ->add('eventType', 'choice', array(
                'label' => 'Type',
                'choices' => self::EVENT_TYPES,
                'choices_as_values' => true,
            ))
            ->add('sites', 'entity', array(
                'class'    => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name',
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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }

    public function getName()
    {
        return 'platformd_eventbundle_eventfindtype';
    }
}
