<?php

namespace Platformd\EventBundle\Form\Type;

use Platformd\SpoutletBundle\Form\Type\GmtOffsetTimezoneType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class GroupEventType extends EventType
{
    protected $eventSubscriber;
    private $tagManager;
    private $authChecker;

    public function __construct(AuthorizationCheckerInterface $authChecker, $eventSubscriber, $tagManager)
    {
        $this->authChecker = $authChecker;
        $this->eventSubscriber = $eventSubscriber;
        $this->tagManager = $tagManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('timezone', GmtOffsetTimezoneType::class, [
                'label' => 'platformd.event.form.timezone',
                'full' => true,
            ])
            ->add('tags', TextType::class, array(
                'label' => 'platformd.event.form.tags',
//                'help' => 'platformd.event.form.tags_help',
                'mapped' => false,
                'data' => $builder->getData() ? $this->tagManager->getConcatenatedTagNames($builder->getData()) : null,
                'required' => false,
            ));

        // Needed to show fields only to admins
        $adminEventSubscriber = new $this->eventSubscriber($builder->getFormFactory(), $this->authChecker);
        $builder->addEventSubscriber($adminEventSubscriber);
    }
}
