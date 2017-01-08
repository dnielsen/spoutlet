<?php

namespace Platformd\GiveawayBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Platformd\GiveawayBundle\Entity\CodeAssignment;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class CodeAssignmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
            ])
            ->add('url', null, [
                'label' => 'Url',
            ])
            ->add('type', ChoiceType::class, array(
                'label' => 'Type',
                'choices' => CodeAssignment::getValidTypes(),
                'choices_as_values' => true,
            ))
            ->add('codesFile', FileType::class, array(
                'label' => 'Codes CSV',
            ))
        ;
    }

    public function getBlockPrefix()
    {
        return 'code_assignment';
    }
}
