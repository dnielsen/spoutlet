<?php

namespace Platformd\SweepstakesBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\SecurityContext;

class SweepstakesEntryType extends AbstractType
{
    protected $securityContext;

    public function __construct(SecurityContext $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        if (!$this->securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $builder->add('registrationDetails', 'platformd_sweeps_registration', array(
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

