<?php

namespace Platformd\UserBundle\Form\Type;

use Platformd\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubscriptionSettingsType extends AbstractType
{
    private $encoderFactory;
    private $securityContext;
    private $apiManager;
    private $apiAuth;

    public function __construct($encoderFactory, $securityContext, $apiManager, $apiAuth)
    {
        $this->encoderFactory  = $encoderFactory;
        $this->securityContext = $securityContext;
        $this->apiManager      = $apiManager;
        $this->apiAuth         = $apiAuth;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user       = $this->securityContext->getToken()->getUser();
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

    public function getName()
    {
        return 'subscription_settings';
    }
}
