<?php

namespace Platformd\GiveawayBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Platformd\GiveawayBundle\Entity\CodeAssignment;
use Symfony\Component\Form\FormBuilderInterface;

class CodeAssignmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text')
            ->add('url', null)
            ->add('type', 'choice', array(
                'choices' => CodeAssignment::getValidTypes(),
            ))
            ->add('codesFile', 'file', array(
                'label' => 'Codes CSV',
            ))
        ;
    }

    public function getName()
    {
        return 'code_assignment';
    }
}
