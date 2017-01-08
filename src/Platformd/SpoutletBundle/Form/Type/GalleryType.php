<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
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
            ->add('slug', SlugType::class, array('url_prefix' => '/galleries/{slug}'))
            ->add('categories', EntityType::class, array(
                'class' => 'SpoutletBundle:GalleryCategory',
                'multiple' => true,
                'expanded' => true,
                'label' => 'Enabled for',
                'choice_label' => 'name',
            ))
            ->add('sites', EntityType::class, array(
                'label' => 'Sites',
                'class'    => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name'
            ))
            ->add('deleted', ChoiceType::class, array(
                'label'     => 'Status',
                'choices'   => self::STATUSES,
                'choices_as_values' => true,
            ))
            ->add('translations', CollectionType::class, array(
                'entry_type' => new GalleryTranslationType,
                'allow_add' => true,
                'allow_delete' => true,
                'required' => false,
                'by_reference' => false,
            ))
        ;
    }

    public function getBlockPrefix()
    {
        return 'platformd_spoutletbundle_gallerytype';
    }
}
