<?php

namespace Platformd\UserBundle\Form\Type;

use Platformd\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AccountSettingsType extends AbstractType
{
    private $encoderFactory;
    private $tokenStorage;
    private $apiManager;
    private $apiAuth;

    public function __construct($encoderFactory, TokenStorageInterface $tokenStorage, $apiManager, $apiAuth)
    {
        $this->encoderFactory = $encoderFactory;
        $this->tokenStorage = $tokenStorage;
        $this->apiManager = $apiManager;
        $this->apiAuth = $apiAuth;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $encoder = $this->encoderFactory->getEncoder($user);
        $apiManager = $this->apiManager;
        $apiAuth = $this->apiAuth;

        $builder
            ->add('currentPassword', PasswordType::class, array(
                'required' => true,
                'error_bubbling' => true,
            ))
            ->add('plainPassword', RepeatedType::class, array(
                'type' => 'password',
                'required' => true,
                'invalid_message' => 'passwords_do_not_match',
                'error_bubbling' => true
            ))
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($encoder, $user, $apiManager, $apiAuth) {
                $data = $event->getData();
                $form = $event->getForm();
                $plainPassword = $data->getPlainPassword();
                $first = $form->get('plainPassword')->get('first')->getData();
                $second = $form->get('plainPassword')->get('second')->getData();

                if ($first != $second) {
                    $form->get('plainPassword')->addError(new FormError('passwords_do_not_match'));
                }

                if (empty($first) && empty($second)) {
                    $form->get('currentPassword')->addError(new FormError('must_enter_new_password'));
                }

                $isPasswordValid = $apiAuth ? $apiManager->authenticate($user, $data->currentPassword, false) : $encoder->isPasswordValid($data->getPassword(), $data->currentPassword, $data->getSalt());

                if (!$isPasswordValid) {
                    $form->get('currentPassword')->addError(new FormError('current_passwords_do_not_match'));
                }
            });
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['Default']
        ]);
    }

    public function getBlockPrefix()
    {
        return 'account_settings';
    }
}
