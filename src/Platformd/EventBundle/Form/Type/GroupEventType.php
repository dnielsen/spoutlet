<?php

namespace Platformd\EventBundle\Form\Type;

use Symfony\Component\Form\FormBuilder,
    Symfony\Component\Security\Core\SecurityContextInterface
;

use Platformd\EventBundle\Form\EventSubscriber\AdminGroupEventSubscriber as EventSubscriber;

class GroupEventType extends EventType
{
    protected $eventSubscriber;

    public function __construct(SecurityContextInterface $security, $eventSubscriber)
    {
        parent::__construct($security);

        $this->eventSubscriber = $eventSubscriber;
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('private', 'choice', array(
            'choices' => array(
                1 => 'platformd.event.form.choice.private_event',
                0 => 'platformd.event.form.choice.public_event'
            ),
            'label' => 'platformd.event.form.private_public',
            'expanded' => true
        ))
        ->add('timezone', 'gmtTimezone', array(
            'label' => 'platformd.event.form.timezone',
            'full' => true,
        ))
        ;

        // Needed to show fields only to admins
        $adminEventSubscriber = new $this->eventSubscriber($builder->getFormFactory(), $this->security);
        $builder->addEventSubscriber($adminEventSubscriber);
    }
}
