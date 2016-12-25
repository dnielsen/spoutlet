<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Platformd\MediaBundle\Form\Type\MediaType;
use Symfony\Component\Form\FormBuilderInterface;

class SubmitImageType extends AbstractType
{
    private $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('galleryImages', 'collection', array(
                'allow_add'     => true,
                'allow_delete'  => false,
                'type'          => new MediaType(),
                'options'   => array(
                    'image_label' => ' ',
                )
            ))
        ;
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_submitimagetype';
    }
}
