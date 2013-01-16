<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Platformd\SpoutletBundle\Entity\GalleryMedia;
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;
use Platformd\SpoutletBundle\Entity\Gallery;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;


class ImageFindType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
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
            ->add('sites', 'choice', array(
                'label' => 'Sites:',
                'expanded' => 'true',
                'multiple' => 'true',
                'choices' => MultitenancyManager::getSiteChoices(),
            ))
            ->add('startDate', 'date', array(
                'label' => 'Upload Start Date:',
                'property_path' => false,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr'   => array(
                    'class' => 'date-picker'
                )
            ))
            ->add('endDate', 'date', array(
                'label' => 'Upload End Date:',
                'property_path' => false,
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
