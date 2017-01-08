<?php

namespace Platformd\EventBundle\Form\Type;

use Platformd\SpoutletBundle\Form\Type\SlugType;
use Symfony\Component\Form\FormBuilderInterface;

class GlobalEventType extends EventType
{
    const PUBLISH = [
        'platformd.event.form.choice.published' => 1,
        'platformd.event.form.choice.unpublished' => 0,
    ];

    private $event;
    private $tagManager;

    public function __construct($event, $tagManager)
    {
        $this->event = $event;
        $this->tagManager = $tagManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('hostedBy', 'text', array(
                'label' => 'platformd.event.form.hosted_by',
                'required' => false
            ))
            ->add('externalUrl', 'text', array(
                'label' => 'platformd.event.form.external_url',
                'help' => 'platformd.event.form.help.external_url',
                'required' => false
            ))
            ->add('published', 'choice', array(
                'choices' => self::PUBLISH,
                'choices_as_values' => true,
                'label' => 'platformd.event.form.published',
                'expanded' => true
            ))
            ->add('translations', 'collection', array(
                'type' => new GlobalEventTranslationType,
                'allow_add' => false,
                'allow_delete' => false,
                'by_reference' => false,
                'required' => false
            ))
            ->add('sites', 'entity', array(
                'class' => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name'
            ))
            ->add('slug', new SlugType(), array(
                'label' => 'platformd.event.form.url'
            ))
            ->add('timezone', 'gmtTimezone', array(
                'label' => 'platformd.event.form.timezone',
            ))
            ->add('tags', 'text', array(
                'label' => 'platformd.event.form.tags',
                'help' => 'platformd.event.form.tags_help',
                'property_path' => false,
                'data' => $builder->getData() ? $this->tagManager->getConcatenatedTagNames($builder->getData()) : null,
                'required' => false,
            ));
    }
}
