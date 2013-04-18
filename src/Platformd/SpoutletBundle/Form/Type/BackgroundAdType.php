<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\MediaBundle\Form\Type\MediaType;

class BackgroundAdType extends AbstractType
{
    private $isNew;

    public function __construct($isNew = false)
    {
        $this->isNew = $isNew;
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('title')
            ->add('published', 'choice', array(
                'choices' => array(
                    '0' => 'Unpublished',
                    '1' => 'Published',
                ),
                'label' => 'Status',
            ))
            ->add('sites', 'entity', array(
                'class' => 'SpoutletBundle:Site',
                'expanded' => true,
                'multiple' => true,
                'property' => 'name',
            ))
            ->add('adSites', 'collection', array(
                'type' => new BackgroundAdSiteType,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ))
             ->add('file', new MediaType(), array(
                'image_label' => 'Image',
                'image_help'  => 'Recommended width: 2000px with the center being 970 pixels wide and pure black.',
                'with_remove_checkbox' => true,
            ))
            ->add('dateStart', 'datetime', array(
                'label' => 'Start date',
                'widget' => 'single_text',
                'attr'   => array(
                    'class' => 'datetime-picker',
                )
            ))
            ->add('dateEnd', 'datetime', array(
                'label' => 'End date',
                'widget' => 'single_text',
                'attr'   => array(
                    'class' => 'datetime-picker',
                )
            ))
            ->add('timezone', 'timezone', array(
                'help' => 'Set the timezone that the start/end times are in.',
            ))
        ;
    }

    public function getName()
    {
        return 'admin_background_ad';
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'validation_groups' => $this->isNew ? array('Default', 'New') : array('Default'),
        );
    }
}
