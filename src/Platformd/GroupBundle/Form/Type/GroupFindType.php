<?php

namespace Platformd\GroupBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
            ->add('groupName', TextType::class, array(
                'label' => 'Name:'
            ))
            ->add('deleted', ChoiceType::class, array(
                'label' => 'Status:',
                'choices' => self::STATUSES,
                'choices_as_values' => true,
                'placeholder' => 'Select All',
                'required' => false,
            ))
            ->add('category', ChoiceType::class, array(
                'label' => 'Category:',
                'choices' => array(
                    'location' => 'Location',
                    'topic' => 'Topic'
                ),
                'choices_as_values' => true,
                'placeholder' => 'Select All',
                'required' => false,
            ))
            ->add('sites', EntityType::class, array(
                'label' => 'Sites',
                'class'    => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name',
            ))
            ->add('startDate', DateType::class, array(
                'label' => 'Start Date:',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr'   => array(
                    'class' => 'date-picker'
                )
            ))
            ->add('endDate', DateType::class, array(
                'label' => 'End Date:',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr'   => array(
                    'class' => 'date-picker'
                )
            ));
    }

    public function getBlockPrefix()
    {
        return 'platformd_groupbundle_groupfindtype';
    }
}
