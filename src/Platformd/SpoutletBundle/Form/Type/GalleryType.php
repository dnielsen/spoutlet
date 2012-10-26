<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Entity\Gallery;
use Platformd\SpoutletBundle\Form\Type\SiteChoiceType;

class GalleryType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('name', null, array(
                'label' => 'Gallery name',
            ))
            ->add('categories', 'choice', array(
                'choices' => self::getCategoryChoices(),
                'multiple' => true,
                'expanded' => true,
                'label' => 'Enabled for',
            ))
            ->add('sites', 'entity', array(
                'class'    => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'property' => 'name'
        ));
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_gallerytype';
    }

    private static function getCategoryChoices()
    {
        $values = Gallery::getValidCategories();

        foreach ($values as $value) {
            $choices[$value]  = $value;
        }

        return $choices;
    }
}
