<?php

namespace Platformd\EventBundle\Form\Type;

use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Form\FormBuilderInterface;

class GroupEventType extends EventType
{
    const PRIVATES = [
        'platformd.event.form.choice.private_event' => 1,
        'platformd.event.form.choice.public_event' => 0,
    ];

    protected $eventSubscriber;
    private $tagManager;

    public function __construct(SecurityContextInterface $security, $eventSubscriber, $tagManager)
    {
        parent::__construct($security);

        $this->eventSubscriber = $eventSubscriber;
        $this->tagManager = $tagManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('private', 'choice', array(
                'choices' => self::PRIVATES,
                'choices_as_values' => true,
                'label' => 'platformd.event.form.private_public',
                'expanded' => true
            ))
            ->add('timezone', 'gmtTimezone', array(
                'label' => 'platformd.event.form.timezone',
                'full' => true,
            ))
            ->add('tags', 'text', array(
                'label' => 'platformd.event.form.tags',
                'help' => 'platformd.event.form.tags_help',
                'property_path' => false,
                'data' => $builder->getData() ? $this->tagManager->getConcatenatedTagNames($builder->getData()) : null,
                'required' => false,
            ));

        // Needed to show fields only to admins
        $adminEventSubscriber = new $this->eventSubscriber($builder->getFormFactory(), $this->security);
        $builder->addEventSubscriber($adminEventSubscriber);
    }
}
