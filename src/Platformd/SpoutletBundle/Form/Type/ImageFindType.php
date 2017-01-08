<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
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
            ->add('title', 'text', array(
                'label' => 'Title:'
            ))
            ->add('deleted', 'choice', array(
                'label' => 'Deleted:',
                'choices' => self::STATUSES,
                'choices_as_values' => true,
                'empty_value' => 'Select All',
                'required' => false,
            ))
            ->add('published', 'choice', array(
                'label' => 'Status:',
                'choices' => self::PUBLISHES,
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
                'label' => 'Upload Start Date:',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr'   => array(
                    'class' => 'date-picker'
                )
            ))
            ->add('endDate', 'date', array(
                'label' => 'Upload End Date:',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr'   => array(
                    'class' => 'date-picker'
                )
            ));
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_imagefindtype';
    }
}
