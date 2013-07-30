<?php

namespace Platformd\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Event\DataEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;

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

    public function buildForm(FormBuilder $builder, array $options)
    {
        $user       = $this->securityContext->getToken()->getUser();
        $encoder    = $this->encoderFactory->getEncoder($user);
        $apiManager = $this->apiManager;
        $apiAuth    = $this->apiAuth;

        $builder->add('subscribedAlienwareEvents');
    }

    public function getDefaultOptions(array $options)
    {
        return array_merge($options, array(
            'data_class' => 'Platformd\UserBundle\Entity\User',
            'validation_groups' => array('Default')
        ));
    }

    public function getName()
    {
        return 'subscription_settings';
    }
}
