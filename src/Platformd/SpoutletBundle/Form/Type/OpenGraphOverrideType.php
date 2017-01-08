<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Platformd\SpoutletBundle\Entity\OpenGraphOverride;
use Symfony\Component\Form\AbstractType;
use Platformd\MediaBundle\Form\Type\MediaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OpenGraphOverrideType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('description', 'textarea', array(
                'label' => 'Description',
                'max_length' => 140
            ))
            ->add('thumbnail', new MediaType(), array(
                'image_label' => 'Thumbnail',
                'image_help'  => 'The large thumbnail image will be resized for this image.  Alternatively, if you choose to upload an image, recommended size is 115 x 100.',
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => OpenGraphOverride::class,
        ]);
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_opengraphoverridetype';
    }
}
