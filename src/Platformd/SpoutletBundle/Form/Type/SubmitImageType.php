<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Entity\GalleryMedia;
use Platformd\SpoutletBundle\Entity\GalleryImage;
use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\UserBundle\Entity\User;

class SubmitImageType extends AbstractType
{
    private $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('galleryImages', 'collection', array(
                'allow_add' => true,
                'allow_delete' => true,
                'type'      => new MediaType(),
                'options'   => array(
                    'image_label' => 'Gallery Image',
                    'image_help'  => 'Only jpg, png and gif images allowed',
                )
            ))
        ;
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_submitimagetype';
    }
}
