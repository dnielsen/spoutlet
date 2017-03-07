<?php

namespace Platformd\SweepstakesBundle\Form\Type;

use Platformd\UserBundle\Form\Type\RegistrationFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SweepstakesEntryType extends AbstractType
{
    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authChecker;

    public function __construct(AuthorizationCheckerInterface $authChecker)
    {
        $this->authChecker = $authChecker;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entry = $builder->getData();
        $sweeps = $entry->getSweepstakes();

        if (!$this->authChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $builder->add('registrationDetails', RegistrationFormType::class, array(
                'property_path' => false,
            ));
        }

        $builder
            ->add('phoneNumber', TextType::class, array(
                'label' => 'sweepstakes.entry.form.phone_number',
            ))
            ->add('answers', CollectionType::class, array(
                'entry_type' => new SweepstakesAnswerType(),
            ))
            ->add('termsAccepted', CheckboxType::class, array(
                'label' => 'sweepstakes.entry.form.read_and_agreed_to_rules',
            ));

        if ($sweeps->getHasOptionalCheckbox()) {
            $builder->add('optionalCheckboxAnswer', CheckboxType::class, array(
                'label' => $sweeps->getOptionalCheckboxLabel(),
            ));
        }
    }

    public function getBlockPrefix()
    {
        return 'sweepstakes_entry';
    }
}
