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
                0 => 'platformd.event.form.choice.private_event',
                1 => 'platformd.event.form.choice.public_event'
            ),
            'label' => 'platformd.event.form.private_public',
            'expanded' => true
        ));
    }
}
