<?php

namespace Platformd\IdeaBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Platformd\IdeaBundle\Entity\RegistrationField;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RegistrationFieldFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', 'choice', array(
                'required' => true,
                'choices' => array(
                    'Text' => RegistrationField::TYPE_TEXT,
                    'Checkbox' => RegistrationField::TYPE_CHECKBOX,
                ),
                'choices_as_values' => true,
            ))
            ->add('question', 'text', array(
                'attr' => array(
                    'size' => '60%'
                ),
                'required' => true
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RegistrationField::class
        ]);
    }

    public function getName()
    {
        return 'registration_field';
    }
}