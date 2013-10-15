<?php

namespace Platformd\GiveawayBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

use Platformd\GiveawayBundle\Entity\CodeAssignment;

class CodeAssignmentType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
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
