<?php

namespace Platformd\EventBundle\Form\Type;

use Symfony\Component\Form\FormBuilder;

class GroupEventType extends EventType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('private', 'choice', array(
            'choices' => array(
                0 => 'Private Event',
                1 => 'Public Event'
            ),
            'label' => 'Is this a public or private event',
            'expanded' => true
        ));
    }
}
