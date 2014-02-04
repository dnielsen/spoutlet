<?php

namespace Platformd\IdeaBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class RegistrationFieldFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('question', 'text', array('attr' => array('size' => '60%'), 'required' => true))
        ;
    }

    public function getName()
    {
        return 'registration_field';
    }

    public function getDefaultOptions(array $options){
        return array('data_class' => 'Platformd\IdeaBundle\Entity\RegistrationField');
    }
}