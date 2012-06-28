<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Entity\Deal;
use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\SpoutletBundle\Form\Type\SiteChoiceType;
use Platformd\SpoutletBundle\Form\Type\SlugType;

class OpenGraphOverrideType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('description', 'textarea', array(
                'label' => 'Description',
                'max_length' => 255
            ))
            ->add('thumbnail', new MediaType(), array(
                'image_label' => 'Thumbnail',
                'image_help'  => 'Recommended size: 115 x 100',
            ))
        ;
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => new \Platformd\SpoutletBundle\Entity\OpenGraphOverride
        );
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_opengraphoverridetype';
    }
}
