<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Platformd\SpoutletBundle\Entity\GalleryTranslation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GalleryTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('siteId', HiddenType::class, array(
                'attr' => array('class' => 'translationSiteId')
            ))
            ->add('name', TextType::class, array(
                'required' => false,
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => GalleryTranslation::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'platformd_spoutletbundle_gallerytranslationtype';
    }
}
