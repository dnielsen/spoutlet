<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class RsvpAttendeeType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('firstName', 'text', array('label' => 'First Name'))
            ->add('lastName', 'text', array('label' => 'Last Name'))
            ->add('email', 'text')
            ->add('phoneNumber', 'text', array('label' => 'Phone Number'))
        ;

        if ($builder->getData()->getRsvp()->isCodeRequired()) {
            $builder->add('code', 'rsvp_code', array(
                'label' => 'RSVP Code',
                'attr' => array(
                    'size' => '12'
                ),
            ));
        }
    }

    public function getName()
    {
        return 'rsvp_attendee';
    }
}

