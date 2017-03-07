<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ImageFindType extends AbstractType
{
    const STATUSES = [
        'Active' => 0,
        'Deleted' => 1,
    ];

    const PUBLISHES = [
        'Published' => 1,
        'Unpublished' => 0,
    ];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, array(
                'label' => 'Title:'
            ))
            ->add('deleted', ChoiceType::class, array(
                'label' => 'Deleted:',
                'choices' => self::STATUSES,
                'choices_as_values' => true,
                'placeholder' => 'Select All',
                'required' => false,
            ))
            ->add('published', ChoiceType::class, array(
                'label' => 'Status:',
                'choices' => self::PUBLISHES,
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
                'label' => 'Upload Start Date:',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr'   => array(
                    'class' => 'date-picker'
                )
            ))
            ->add('endDate', DateType::class, array(
                'label' => 'Upload End Date:',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr'   => array(
                    'class' => 'date-picker'
                )
            ));
    }

    public function getBlockPrefix()
    {
        return 'platformd_spoutletbundle_imagefindtype';
    }
}
