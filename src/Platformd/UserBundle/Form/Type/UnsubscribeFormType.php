<?php

namespace Platformd\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

class UnsubscribeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('unsubscribe', CheckboxType::class, array(
            'required' => false,
        ));
        $builder->add('email', HiddenType::class, array(

        ));
    }

    public function getBlockPrefix()
    {
        return 'platformd_unsubscribe_form';
    }
}
