<?php

namespace Platformd\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
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
                'required'          => true,
                'error_bubbling'    => true,
            ))
            ->add('plainPassword', 'repeated', array(
                'type'              => 'password',
                'required'          => true,
                'invalid_message'   => 'passwords_do_not_match',
                'error_bubbling'    => true
            ))
            ->addEventListener(FormEvents::POST_BIND, function(DataEvent $event) use ($encoder, $user, $apiManager, $apiAuth) {
                $data           = $event->getData();
                $form           = $event->getForm();
                $plainPassword  = $data->getPlainPassword();
                $first          = $form->get('plainPassword')->get('first')->getData();
                $second         = $form->get('plainPassword')->get('second')->getData();

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

