<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Form\Type\GalleryTranslationType;

class GalleryTranslationType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('siteId', 'hidden', array(
                'attr' => array('class' => 'translationSiteId')
            ))
            ->add('name', 'text', array(
                'required' => false,
            ))
        ;
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_gallerytranslationtype';
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Platformd\SpoutletBundle\Entity\GalleryTranslation',
        );
    }
}
