<?php

namespace Platformd\EventBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
            ->add('eventName', TextType::class, [
                'label' => 'Name:'
            ])
            ->add('published', ChoiceType::class, [
                'label' => 'Status:',
                'choices' => self::STATUS,
                'choices_as_values' => true,
                'placeholder' => 'Select All',
                'required' => false,
            ])
            ->add('eventType', ChoiceType::class, [
                'label' => 'Type',
                'choices' => self::EVENT_TYPES,
                'choices_as_values' => true,
            ])
            ->add('sites', EntityType::class, [
                'label' => 'Sites',
                'class'    => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name',
            ])
            ->add('from', DateType::class, [
                'label' => 'Starts After:',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr'   => array(
                    'class' => 'date-picker'
                )
            ])
            ->add('thru', DateType::class, [
                'label' => 'Starts Before:',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr'   => array(
                    'class' => 'date-picker'
                )
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'platformd_eventbundle_eventfindtype';
    }
}
