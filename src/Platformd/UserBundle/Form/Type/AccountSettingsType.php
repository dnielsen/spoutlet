<?php

namespace Platformd\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Platformd\UserBundle\Form\Type\UserAvatarType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Event\DataEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class AccountSettingsType extends AbstractType
{
    private $encoder;

    public function __construct(PasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
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
            ->add('userAvatars', 'collection', array(
                'type'         => new UserAvatarType,
                'allow_add'    => true,
                'allow_delete' => true,
                'by_reference' => false,
            ))
            ->addEventListener(FormEvents::POST_BIND, function(DataEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();
                $plainPassword = $data->getPlainPassword();
                if (empty($data->currentPassword) && empty($plainPassword)) {
                    return;
                }
                if (!$this->encoder->isPasswordValid($data->getPassword(), $data->currentPassword, $data->getSalt())) {
                    $form->get('currentPassword')->addError(new FormError('Current password doesn\'t match'));
                }
            });
        ;
    }

    public function getDefaultOptions(array $options)
    {
        return array_merge($options, array(
            'data_class' => 'Platformd\UserBundle\Entity\User',
            'validation_groups' => array('Default', 'Avatar')
        ));
    }

    public function getName()
    {
        return 'account_settings';
    }
}

