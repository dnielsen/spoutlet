<?php

namespace Platformd\UserBundle\Form\Type;

use Platformd\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SubscriptionSettingsType extends AbstractType
{
    private $encoderFactory;
    private $tokenStorage;
    private $apiManager;
    private $apiAuth;

    public function __construct($encoderFactory, TokenStorageInterface $tokenStorage, $apiManager, $apiAuth)
    {
        $this->encoderFactory  = $encoderFactory;
        $this->tokenStorage = $tokenStorage;
        $this->apiManager      = $apiManager;
        $this->apiAuth         = $apiAuth;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user       = $this->tokenStorage->getToken()->getUser();
//        $encoder    = $this->encoderFactory->getEncoder($user);
//        $apiManager = $this->apiManager;
//        $apiAuth    = $this->apiAuth;

        $builder->add('subscribedAlienwareEvents');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['Default'],
        ]);
    }

    public function getBlockPrefix()
    {
        return 'subscription_settings';
    }
}
