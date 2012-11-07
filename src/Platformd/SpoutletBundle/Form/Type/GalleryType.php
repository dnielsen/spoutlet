<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Form\Type\SiteChoiceType;

class GalleryType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('name', null, array(
                'label' => 'Gallery name',
            ))
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
            ->add('deleted', 'checkbox', array(
                'label' => 'Deleted'
        ));
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_gallerytype';
    }
}
