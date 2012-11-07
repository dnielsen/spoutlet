<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Extension\Core\ChoiceList\ArrayChoiceList;
use Doctrine\ORM\EntityRepository;
use Platformd\UserBundle\Entity\User;

class GalleryMediaType extends AbstractType
{
    private $user;

    public function __construct($user) {
        $this->user = $user;
    }

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
        $builder->add('galleries', null, array(
            'label' => 'Galleries',
            'expanded' => true,
            'multiple' => true,
        ));

        if ($this->user instanceof User && $this->user->hasRole('ROLE_SUPER_ADMIN')) {
                $builder->add('featured', 'checkbox', array(
                    'label' => 'Featured',
                    'help'  => 'Check this checkbox to make this media item featured on the gallery front page.',
                ));
            }
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_gallery_media';
    }
}
