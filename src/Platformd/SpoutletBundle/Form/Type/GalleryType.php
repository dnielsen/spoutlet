<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class GalleryType extends AbstractType
{
    const STATUSES = [
        'Published' => 0,
        'Unpublished' => 1,
    ];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, array(
                'label' => 'Gallery name',
            ))
            ->add('slug', new SlugType(), array('url_prefix' => '/galleries/{slug}'))
            ->add('categories', 'entity', array(
                'class' => 'SpoutletBundle:GalleryCategory',
                'multiple' => true,
                'expanded' => true,
                'label' => 'Enabled for',
                'choice_label' => 'name',
            ))
            ->add('sites', 'entity', array(
                'class'    => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name'
            ))
            ->add('deleted', 'choice', array(
                'label'     => 'Status',
                'choices'   => self::STATUSES,
                'choices_as_values' => true,
            ))
            ->add('translations', 'collection', array(
                'type' => new GalleryTranslationType,
                'allow_add' => true,
                'allow_delete' => true,
                'required' => false,
                'by_reference' => false,
            ))
        ;
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_gallerytype';
    }
}
