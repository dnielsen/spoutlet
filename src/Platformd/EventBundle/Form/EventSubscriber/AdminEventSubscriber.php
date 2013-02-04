<?php

namespace Platformd\EventBundle\Form\EventSubscriber;

use Symfony\Component\Form\Event\DataEvent,
    Symfony\Component\Form\FormFactoryInterface,
    Symfony\Component\EventDispatcher\EventSubscriberInterface,
    Symfony\Component\Form\FormEvents,
    Symfony\Component\Security\Core\SecurityContextInterface
;

use Platformd\EventBundle\Entity\Event;

class AdminEventSubscriber implements EventSubscriberInterface
{
    private $factory;
    private $security;

    public function __construct(FormFactoryInterface $factory, SecurityContextInterface $security)
    {
        $this->factory = $factory;
        $this->security = $security;
    }

    public static function getSubscribedEvents()
    {
        return array(FormEvents::PRE_SET_DATA => 'preSetData');
    }

    public function preSetData(DataEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (null === $data) {
            return;
        }

        // If User is a super admin, they have more options
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            $form->add($this->factory->createNamed('choice', 'registrationOption', null, array(
                'choices' => Event::getRegistrationOptions(true),
                'expanded' => true,
                'label' => 'Event options'
            )));
            $form->add($this->factory->createNamed('text', 'externalUrl', null, array(
                'label' => 'External URL',
                'help' => '(Optional) If filled in, this URL will override the destination of any links that would normally point to the Event page.',
                'required' => false
            )));
        } else {
            $form->add($this->factory->createNamed('choice', 'registrationOption', null, array(
                'choices' => Event::getRegistrationOptions(),
                'expanded' => true,
                'label' => 'Event options'
            )));
        }
    }
}
