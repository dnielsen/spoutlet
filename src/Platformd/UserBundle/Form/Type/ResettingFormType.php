<?php

namespace Platformd\UserBundle\Form\Type;

use FOS\UserBundle\Form\Type\ResettingFormType as BaseType;
use Symfony\Component\Form\FormBuilderInterface;

class ResettingFormType extends BaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('new', 'repeated', array('type' => 'password', 'invalid_message' => 'passwords_do_not_match'));
    }

    public function getName()
    {
        return 'platformd_user_resetting';
    }
}
