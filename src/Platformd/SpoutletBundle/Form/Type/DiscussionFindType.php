<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
            ->add('discussionName', TextType::class, array(
                'label' => 'Name:'
            ))
            ->add('deleted', ChoiceType::class, array(
                'label' => 'Status:',
                'choices' => self::STATUSES,
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
            ->add('from', DateType::class, array(
                'label' => 'Start Date:',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr'   => array(
                    'class' => 'date-picker'
                )
            ))
            ->add('thru', DateType::class, array(
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
        return 'platformd_spoutletbundle_discussionfindtype';
    }
}
