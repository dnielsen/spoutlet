<?php

namespace Platformd\SweepstakesBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Security\Core\User\UserInterface;

class SweepstakesEntryType extends AbstractType
{
    protected $user;

    public function __construct(UserInterface $user=null)
    {
        $this->user = $user;
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        if (null === $this->user) {
            $builder->add('registrationDetails', 'platformd_user_registration', array(
                'property_path' => false,
            ));
        }

        $builder
            ->add('phoneNumber', 'text', array(
                'label'         => 'sweepstakes.entry.form.phone_number',
            ))
            ->add('answers', 'collection', array(
                'type' => new SweepstakesAnswerType(),
            ))
            ->add('termsAccepted', 'checkbox', array(
                'label' => 'sweepstakes.entry.form.read_and_agreed_to_rules',
            ))
        ;
    }

    public function getName()
    {
        return 'sweepstakes_entry';
    }
}

