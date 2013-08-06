<?php

namespace Platformd\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class UnsubscribeFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('unsubscribe', 'checkbox', array(
            'required' => false,
        ));
        $builder->add('email', 'hidden', array(

        ));

    }

    public function getName()
    {
        return 'platformd_unsubscribe_form';
    }
}
