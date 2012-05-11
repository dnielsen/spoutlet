<?php

namespace Platformd\SpoutletBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class GameType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('slug')
            ->add('category')
            ->add('facebookFanpageUrl')
            ->add('logo')
            ->add('publisherLogos')
        ;
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_gametype';
    }
}
