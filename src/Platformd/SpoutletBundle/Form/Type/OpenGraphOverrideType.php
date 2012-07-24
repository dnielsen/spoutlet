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
                'help' => 'Needs to be 140 characters or less. Tone: social, casual, conversational description that appears on someone\'s facebook wall/timeline when that person likes the page.
',
                'max_length' => 140
            ))
            ->add('thumbnail', new MediaType(), array(
                'image_label' => 'Thumbnail',
                'image_help'  => 'The large thumbnail image will be resized for this image.  Alternatively, if you choose to upload an image, recommended size is 115 x 100.',
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
