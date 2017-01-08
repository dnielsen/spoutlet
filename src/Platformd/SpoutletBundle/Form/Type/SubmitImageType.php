<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Platformd\MediaBundle\Form\Type\MediaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
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
            ->add('galleryImages', CollectionType::class, array(
                'allow_add'     => true,
                'allow_delete'  => false,
                'entry_type'          => new MediaType(),
                'entry_options'   => array(
                    'image_label' => ' ',
                )
            ))
        ;
    }

    public function getBlockPrefix()
    {
        return 'platformd_spoutletbundle_submitimagetype';
    }
}
