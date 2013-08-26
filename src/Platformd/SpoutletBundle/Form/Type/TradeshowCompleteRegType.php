<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class TradeshowCompleteRegType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('username', 'text', array(
            'required' => true,
        ));

        $builder->add('password', 'password', array(
            'required' => true,
        ));
    }

    public function getName()
    {
        return 'platformd_tradeshow_registration';
    }
}
