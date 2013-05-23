<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Form\Type\GalleryTranslationType;

class GalleryType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
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
                'property' => 'name',
            ))
            ->add('sites', 'entity', array(
                'class'    => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'property' => 'name'
            ))
            ->add('deleted', 'choice', array(
                'label'     => 'Status',
                'choices'   => array(
                    0 => 'Published',
                    1 => 'Unpublished',
                ),
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
