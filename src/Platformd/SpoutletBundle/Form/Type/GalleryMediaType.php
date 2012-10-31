<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Extension\Core\ChoiceList\ArrayChoiceList;
use Doctrine\ORM\EntityRepository;

class GalleryMediaType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('title', 'text', array(
            'max_length' => 255,
            'label'      => 'Image Name',
            'attr'       => array('class' => 'photo-title')
        ));
        $builder->add('description', 'textarea', array(
            'max_length' => 512,
            'label'      => 'Description',
            'attr'       => array('class' => 'photo-description')
        ));
        $builder->add('galleries', new GalleryChoiceType(), array(
            'label' => 'Galleries'
        ));
        $builder->add('mediaId', 'hidden');
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_gallery_media';
    }
}
