<?php

namespace Platformd\IdeaBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Platformd\IdeaBundle\Entity\RegistrationField;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegistrationFieldFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', ChoiceType::class, array(
                'required' => true,
                'choices' => array(
                    'Text' => RegistrationField::TYPE_TEXT,
                    'Checkbox' => RegistrationField::TYPE_CHECKBOX,
                ),
                'choices_as_values' => true,
            ))
            ->add('question', TextType::class, array(
                'attr' => array(
                    'size' => '60%'
                ),
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RegistrationField::class
        ]);
    }

    public function getBlockPrefix()
    {
        return 'registration_field';
    }
}
