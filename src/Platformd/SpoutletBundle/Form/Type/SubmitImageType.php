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

    }

    public function getName()
    {
        return 'platformd_spoutletbundle_submitimagetype';
    }
}
