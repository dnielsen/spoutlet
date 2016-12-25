<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ImageFindType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', 'text', array(
                'label' => 'Title:'
            ))
            ->add('deleted', 'choice', array(
                'label' => 'Deleted:',
                'choices' => array(
                    '0' => 'Active',
                    '1' => 'Deleted'
                ),
                'empty_value' => 'Select All',
                'required' => false,
            ))
            ->add('published', 'choice', array(
                'label' => 'Status:',
                'choices' => array(
                    '1' => 'Published',
                    '0' => 'Unpublished'
                ),
                'empty_value' => 'Select All',
                'required' => false,
            ))
            ->add('sites', 'entity', array(
                'class'    => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'property' => 'name',
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
