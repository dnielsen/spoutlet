<?php

namespace Platformd\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use FOS\UserBundle\Form\Type\ResettingFormType as BaseType;

class ResettingFormType extends BaseType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('new', 'repeated', array('type' => 'password', 'invalid_message' => 'passwords_do_not_match'));
    }

    public function getName()
    {
        return 'platformd_user_resetting';
    }
}
