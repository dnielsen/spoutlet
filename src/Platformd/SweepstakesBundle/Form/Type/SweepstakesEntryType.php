<?php

namespace Platformd\SweepstakesBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class SweepstakesEntryType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('phoneNumber', 'text', array(
                'label'         => 'sweepstakes.entry.form.phone_number',
                'property_path' => false,
            ))
            ->add('answers', 'collection', array(
                'type' => new SweepstakesAnswerType(),
            ))
        ;
    }

    public function getName()
    {
        return 'sweepstakes_entry';
    }
}

