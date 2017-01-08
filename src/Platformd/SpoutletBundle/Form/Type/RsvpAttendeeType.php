<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class RsvpAttendeeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', TextType::class, array('label' => 'First Name'))
            ->add('lastName', TextType::class, array('label' => 'Last Name'))
            ->add('email', TextType::class)
            ->add('phoneNumber', TextType::class, array('label' => 'Phone Number'))
        ;

        if ($builder->getData()->getRsvp()->isCodeRequired()) {
            $builder->add('code', RsvpCodeType::class, array(
                'label' => 'RSVP Code',
                'attr' => array(
                    'size' => '12'
                ),
            ));
        }
    }

    public function getBlockPrefix()
    {
        return 'rsvp_attendee';
    }
}
