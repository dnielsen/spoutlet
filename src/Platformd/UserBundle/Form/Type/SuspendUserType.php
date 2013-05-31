<?php

namespace Platformd\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class SuspendUserType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('expiredUntil', 'datetime', array(
                'required' => false,
               'label' => 'Expires until',
                'widget' => 'single_text',
                'attr'   => array(
                    'class' => 'datetime-picker',
                )
            ))
        ;
    }

    public function getName()
    {
        return 'user_suspend';
    }
}

