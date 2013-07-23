<?php

namespace Platformd\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Platformd\UserBundle\Form\Type\UserAvatarType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Event\DataEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;

class AccountSettingsType extends AbstractType
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

        $builder
            ->add('currentPassword', 'password', array(
                'required' => false,
            ))
            ->add('plainPassword', 'repeated', array(
                'type' => 'password',
                'required' => false,
                'options' => array('label' => 'New password'),
            ))
            ->add('subscribedGamingNews', null, array(
                'label' => 'Subscribe to newsletter',
            ))
            ->add('subscribedAlienwareEvents', null, array(
                'label' => 'Subscribe to Alienware Events',
            ))
            ->addEventListener(FormEvents::POST_BIND, function(DataEvent $event) use ($encoder) {
            ->addEventListener(FormEvents::POST_BIND, function(DataEvent $event) use ($encoder, $user, $apiManager, $apiAuth) {
                $data = $event->getData();
                $form = $event->getForm();
                $plainPassword = $data->getPlainPassword();
                if (empty($plainPassword)) {
                    return;
                }

                $isPasswordValid = $apiAuth ? $apiManager->authenticate($user, $data->currentPassword) : $encoder->isPasswordValid($data->getPassword(), $data->currentPassword, $data->getSalt());

                if (!$isPasswordValid) {
                    $form->get('currentPassword')->addError(new FormError('Current password doesn\'t match'));
                }
            });
        ;
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
        return 'account_settings';
    }
}

