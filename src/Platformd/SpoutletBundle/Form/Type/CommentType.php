<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class GameType extends AbstractType
{

    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('body', 'textarea');
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_commenttype';
    }

}
